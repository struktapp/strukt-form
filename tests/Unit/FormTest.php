<?php

use Strukt\Contract\FormInterface;
use Strukt\Form\Fixtures\AfterRule;
use Strukt\Form\Fixtures\EvenRule;
use Strukt\Form\Fixtures\UserForm;
use Strukt\Form\Form;
use Strukt\Rules\Fields;
use Strukt\Rules\RuleRegistry;

it('satisfies the form contract', function (): void {
    $form = new UserForm();

    expect($form)->toBeInstanceOf(FormInterface::class)
        ->and($form)->toBeInstanceOf(Strukt\Rules\Contract\FieldSource::class);
});

it('reports its field names from the rules definition', function (): void {
    $form = new UserForm();

    expect($form->fields())->toBe([
        'name',
        'email',
        'password',
        'password_confirmation',
        'age',
        'role',
        'terms',
    ])
        ->and($form->hasField('email'))->toBeTrue()
        ->and($form->hasField('nope'))->toBeFalse();
});

it('reads and writes single values', function (): void {
    $form = new Form();
    $form->setValue('name', 'Ada');

    expect($form->value('name'))->toBe('Ada')
        ->and($form->value('missing', 'fallback'))->toBe('fallback')
        ->and($form->values())->toBe(['name' => 'Ada']);
});

it('merges several values and keeps what is already set', function (): void {
    $form = new Form();
    $form->setValue('name', 'Ada');
    $form->setValues(['email' => 'ada@example.com', 'name' => 'Grace']);

    expect($form->values())->toBe([
        'name' => 'Grace',
        'email' => 'ada@example.com',
    ]);
});

it('tracks the submitted state', function (): void {
    $form = new Form();

    expect($form->isSubmitted())->toBeFalse();

    $form->submit(['name' => 'Ada']);

    expect($form->isSubmitted())->toBeTrue()
        ->and($form->value('name'))->toBe('Ada');
});

it('distinguishes a stored null from a missing value', function (): void {
    $form = (new Form())->setValue('age', null);

    expect($form->value('age', 'fallback'))->toBeNull()
        ->and($form->value('missing', 'fallback'))->toBe('fallback');
});

it('has no errors before validating', function (): void {
    $form = new UserForm();

    expect($form->hasErrors())->toBeFalse()
        ->and($form->errors())->toBeEmpty()
        ->and($form->errorsFor('email'))->toBeEmpty();
});

it('validates a complete submission', function (): void {
    $form = (new UserForm())->submit([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'analytical-engine',
        'password_confirmation' => 'analytical-engine',
        'age' => 36,
        'role' => 'admin',
        'terms' => true,
    ]);

    expect($form->validate())->toBeTrue()
        ->and($form->hasErrors())->toBeFalse();
});

it('reports every failing field of a bad submission', function (): void {
    $form = (new UserForm())->submit([
        'name' => 'A',
        'email' => 'nope',
        'password' => 'short',
        'password_confirmation' => 'different',
        'age' => 12,
        'role' => 'superuser',
        'terms' => '',
    ]);

    expect($form->validate())->toBeFalse()
        ->and(array_keys($form->errors()))->toBe(['name', 'email', 'password', 'password_confirmation', 'age', 'role', 'terms'])
        ->and($form->errorsFor('email'))->toBe(['Email must be a valid email address.'])
        ->and($form->errorsFor('age'))->toBe(['Age must be at least 18.'])
        ->and($form->errorsFor('role'))->toBe(['Role must be one of: admin, editor, viewer.']);
});

it('prefers a field specific message override over a rule wide one', function (): void {
    $form = (new UserForm())->submit([
        'name' => 'Ada Lovelace',
        'email' => '',
        'password' => 'analytical-engine',
        'password_confirmation' => 'analytical-engine',
        'role' => 'admin',
        'terms' => true,
    ]);
    $form->validate();

    expect($form->errorsFor('email'))->toBe(['We need an email address to reach you.']);
});

it('applies a rule wide override to every field that uses the rule', function (): void {
    $form = (new UserForm())->submit([
        'name' => 'A',
        'password' => 'analytical-engine',
        'password_confirmation' => 'analytical-engine',
        'role' => 'admin',
        'terms' => true,
    ]);
    $form->validate();

    expect($form->errorsFor('name'))->toContain('Name is too short, use at least 2 characters.');
});

it('validates in one call with validateWith', function (): void {
    expect((new UserForm())->validateWith([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'analytical-engine',
        'password_confirmation' => 'analytical-engine',
        'role' => 'admin',
        'terms' => true,
    ]))->toBeTrue();
});

it('clears values, errors, and the submitted flag on reset', function (): void {
    $form = (new UserForm())->submit(['email' => 'nope']);
    $form->validate();
    $form->reset();

    expect($form->values())->toBeEmpty()
        ->and($form->errors())->toBeEmpty()
        ->and($form->isSubmitted())->toBeFalse();
});

it('creates a fresh submitted form with validated', function (): void {
    $form = (new UserForm())->submit(['email' => 'nope']);
    $form->validate();

    $fresh = $form->validated(['name' => 'Ada Lovelace']);

    expect($fresh)->not->toBe($form)
        ->and($fresh->errors())->toBeEmpty()
        ->and($fresh->isSubmitted())->toBeTrue()
        ->and($fresh->value('name'))->toBe('Ada Lovelace');
});

it('creates an empty form with the static create method', function (): void {
    $form = UserForm::create();

    expect($form)->toBeInstanceOf(UserForm::class)
        ->and($form->values())->toBeEmpty();
});

it('uses a form specific registry for custom rules', function (): void {
    $form = new class extends Form {
        protected function registry(): RuleRegistry
        {
            return RuleRegistry::create()->extend('even', new EvenRule('number'));
        }

        public function rules(): array
        {
            return ['number' => 'required|even'];
        }
    };

    expect($form->validateWith(['number' => 4]))->toBeTrue()
        ->and($form->validateWith(['number' => 3]))->toBeFalse()
        ->and($form->errorsFor('number'))->toBe(['Number must be an even number.']);
});

it('gives a custom rule access to sibling values', function (): void {
    $form = new class extends Form {
        protected function registry(): RuleRegistry
        {
            return RuleRegistry::create()->extend('after', fn (string $field, array $params): Strukt\Rules\Contract\RuleInterface => new AfterRule($field, $params[0] ?? ''));
        }

        public function rules(): array
        {
            return ['end' => 'required|date|after:start', 'start' => 'required|date'];
        }
    };

    expect($form->validateWith(['start' => '2020-01-01', 'end' => '2024-01-01']))->toBeTrue()
        ->and($form->validateWith(['start' => '2024-01-01', 'end' => '2020-01-01']))->toBeFalse()
        ->and($form->errorsFor('end'))->toBe(['End must be later than start.']);
});

it('builds a form with the form helper', function (): void {
    $form = form(UserForm::class, [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'analytical-engine',
        'password_confirmation' => 'analytical-engine',
        'role' => 'admin',
        'terms' => true,
    ]);

    expect($form)->toBeInstanceOf(UserForm::class)
        ->and($form->isSubmitted())->toBeTrue()
        ->and($form->validate())->toBeTrue();
});

it('builds an unsubmitted form with the form helper', function (): void {
    $form = form(UserForm::class, ['email' => 'ada@example.com'], false);

    expect($form->isSubmitted())->toBeFalse()
        ->and($form->value('email'))->toBe('ada@example.com');
});

it('refuses to build a class that is not a form', function (): void {
    form(Fields::class);
})->throws(InvalidArgumentException::class, 'must extend');

it('validates a plain array with the form_check helper', function (): void {
    expect(form_check(['age' => 20], ['age' => 'required|integer|min:18']))->toBeEmpty()
        ->and(form_check(['age' => 12], ['age' => 'required|integer|min:18']))
        ->toBe(['age' => ['Age must be at least 18.']]);
});