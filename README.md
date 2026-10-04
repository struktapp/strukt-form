# Strukt Form

Rule based form definition, validation, and error reporting, built on the
Strukt rules engine.

A form is described by its rules. There is no docblock parsing, no reflection,
and no request object to hand: you give the form an array of submitted values
and it tells you which fields failed and why. The validation engine lives in
the separate `strukt/rules` package; this package contributes the form
definition, the submitted values, and the collected failures.

```php
use Strukt\Form\Form;

final class LoginForm extends Form
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|min_length:8',
        ];
    }
}

$form = new LoginForm();
$form->submit($_POST);

if ($form->validate() === false) {
    foreach ($form->errors() as $field => $messages) {
        // ['email' => ['Email must be a valid email address.']]
    }
}
```

## Install

```bash
composer require strukt/form
```

## Defining a form

Extend `Form` and return the rules, keyed by field name. Each entry is a pipe
separated string, or a list of rule tokens when you prefer to build the list
programmatically.

```php
public function rules(): array
{
    return [
        'name'     => 'required|string|min_length:2|max_length:50',
        'email'    => ['required', 'email', 'max_length:120'],
        'age'      => 'nullable|integer|min:18',
        'role'     => 'required|in:admin,editor,viewer',
        'tags'     => 'array|max_count:5',
        'bio'      => 'nullable|max_length:500',
    ];
}
```

The full rule list, message overrides, and custom rules are documented in the
[`strukt/rules`](../rules) package.

## Working with a form

| Method | Purpose |
| --- | --- |
| `fields()` | Field names, in declaration order. |
| `rules()` | The rules definition. |
| `value($field, $default = null)` | One submitted value. |
| `setValue($field, $value)` | Set one value. |
| `setValues(array $values)` | Merge several values. |
| `values()` | Every submitted value. |
| `isSubmitted()` | Whether values have been submitted. |
| `submit(array $values = [])` | Merge values and mark as submitted. |
| `validate()` | Run the rules, return `true` when everything passes. |
| `validateWith(array $values)` | `submit()` then `validate()`. |
| `hasErrors()` | Whether any field failed. |
| `errors()` | Failure messages keyed by field. |
| `errorsFor($field)` | Failure messages for one field. |
| `reset()` | Clear values, errors, and the submitted flag. |
| `validated(array $values)` | A fresh submitted form, carrying no failures. |

`submit()`, `setValue()`, and `setValues()` accumulate, so a second call adds to
the submission. Call `reset()` first, or use `validated()`, to start over.

```php
$form->validateWith(['email' => 'ada@example.com', 'password' => 'analytical-engine']);

$form->validate();          // true
$form->hasErrors();         // false
$form->errors();            // []
$form->errorsFor('email');  // []

$fresh = $form->validated($otherSubmission);  // no errors carried over
```

## Messages

Override a failure message per rule, or for one field with `field.rule`. A
field specific override wins. An override is a complete message, used as
written, and may still use `:field`, `:0`, `:1`, `:list`, and `:format`.

```php
public function messages(): array
{
    return [
        'email.required' => 'We need an email address to reach you.',
        'terms.required' => 'You must accept the terms to continue.',
        'min_length'     => ':field is too short, use at least :0 characters.',
    ];
}
```

## Custom rules for one form

Register a custom rule on a form-specific registry so the rest of the
application is not affected:

```php
use Strukt\Rules\RuleRegistry;

final class ReservationForm extends Form
{
    protected function registry(): RuleRegistry
    {
        return RuleRegistry::create()
            ->extend('after', fn (string $field, array $params) => new AfterRule($field, $params[0]));
    }

    public function rules(): array
    {
        return [
            'end'   => 'required|date|after:start',
            'start' => 'required|date',
        ];
    }
}
```

## Helpers

```php
form(LoginForm::class, ['email' => $email, 'password' => $password]);
form(LoginForm::class, $values, submit: false);  // fill without submitting

form_check(['age' => 12], ['age' => 'required|integer|min:18']);
```

`form_check()` validates a plain array against a rules definition without
needing a form class.

## Tests

```bash
composer test
composer lint
```

## License

MIT. See [LICENSE](LICENSE).