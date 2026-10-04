<?php

namespace Strukt\Form;

use Strukt\Contract\FormInterface;
use Strukt\Rules\Contract\FieldSource as RuleFieldSource;
use Strukt\Rules\Messages;
use Strukt\Rules\RuleRegistry;
use Strukt\Rules\Validator;

/**
 * A form definition with submitted values and recorded validation failures.
 *
 * A form is described by its rules, keyed by field name. Subclasses override
 * {@see rules()}, and optionally {@see messages()} to reword failures:
 *
 * ```php
 * final class LoginForm extends Form
 * {
 *     public function rules(): array
 *     {
 *         return [
 *             'email' => 'required|email',
 *             'password' => 'required|min_length:8',
 *         ];
 *     }
 * }
 * ```
 *
 * The form is mutable while it holds a submission, which is what lets
 * `submit()`, `validate()`, and `errors()` read as a sequence. {@see validated()}
 * returns a fresh, unvalidated form for cases where a new submission is needed.
 *
 * The validation engine lives in the separate `strukt/rules` package, so a
 * form here is only the definition, the submitted values, and the collected
 * failures.
 */
class Form implements FormInterface, RuleFieldSource
{
    /**
     * Submitted values keyed by field name.
     *
     * @var array<string, mixed>
     */
    protected array $values = [];

    /**
     * Recorded failure messages keyed by field name.
     *
     * @var array<string, list<string>>
     */
    protected array $errors = [];

    /**
     * Whether values have been submitted.
     */
    protected bool $submitted = false;

    /**
     * Rules keyed by field name.
     *
     * Each value is either a pipe separated string or a list of rule tokens.
     * A list may also hold {@see \Strukt\Rules\Contract\RuleInterface} instances.
     *
     * @return array<string, string|list<string|\Strukt\Rules\Contract\RuleInterface>> Rules.
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Message overrides keyed by rule name or by `field.rule`.
     *
     * @return array<string, string> Overrides.
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Returns the registry used to resolve rule names.
     *
     * Override this to give one form its own custom rules without affecting the
     * shared registry.
     *
     * @return RuleRegistry Registry used to resolve rule names.
     */
    protected function registry(): RuleRegistry
    {
        return RuleRegistry::shared();
    }

    /**
     * Returns the field names the form defines.
     *
     * @return list<string> Field names, in declaration order.
     */
    public function fields(): array
    {
        return array_map('strval', array_keys($this->rules()));
    }

    /**
     * Reports whether the form declares a field.
     *
     * @param string $field Field name.
     * @return bool True when the field has rules.
     */
    public function hasField(string $field): bool
    {
        return array_key_exists($field, $this->rules());
    }

    public function value(string $field, mixed $default = null): mixed
    {
        return array_key_exists($field, $this->values)
            ? $this->values[$field]
            : $default;
    }

    public function setValue(string $field, mixed $value): static
    {
        $this->values[$field] = $value;

        return $this;
    }

    /**
     * Assigns several values at once, keeping any already assigned.
     *
     * @param array<string, mixed> $values Field values keyed by field name.
     * @return static This form.
     */
    public function setValues(array $values): static
    {
        foreach ($values as $field => $value) {
            $this->values[(string) $field] = $value;
        }

        return $this;
    }

    public function values(): array
    {
        return $this->values;
    }

    public function isSubmitted(): bool
    {
        return $this->submitted;
    }

    /**
     * Marks the form as submitted.
     *
     * Values are merged, so a second call adds to the submission rather than
     * replacing it. Use {@see reset()} first to start over.
     *
     * @param array<string, mixed> $values Field values keyed by field name.
     * @return static This form.
     */
    public function submit(array $values = []): static
    {
        $this->setValues($values);
        $this->submitted = true;

        return $this;
    }

    public function validate(): bool
    {
        $this->errors = (new Validator($this->registry(), new Messages($this->messages())))
            ->validate($this->values, $this->rules());

        return $this->errors === [];
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function errorsFor(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    public function reset(): static
    {
        $this->values = [];
        $this->errors = [];
        $this->submitted = false;

        return $this;
    }

    /**
     * Submits the given values, validates, and returns the result.
     *
     * @param array<string, mixed> $values Field values keyed by field name.
     * @return bool True when every rule passes.
     */
    public function validateWith(array $values = []): bool
    {
        return $this->submit($values)->validate();
    }

    /**
     * Returns a fresh form of the same class with the given values submitted.
     *
     * @param array<string, mixed> $values Field values keyed by field name.
     * @return static A new submitted form, carrying no failures.
     */
    public function validated(array $values = []): static
    {
        return static::create()->submit($values);
    }

    /**
     * Creates a form of the called class.
     *
     * @return static New form.
     */
    public static function create(): static
    {
        return new static();
    }
}