<?php

use Strukt\Form\Form;

if (function_exists('helper')) {
    helper('form');
}

if (!function_exists('form') && function_exists('helper_add') && helper_add('form')) {
    /**
     * Builds a form class and submits values to it.
     *
     * Example:
     * ```php
     * $f = form(LoginForm::class, ['email' => 'a@b.co', 'password' => 'secret']);
     *
     * $f->validate();
     * $f->errorsFor('email');
     * ```
     *
     * @param class-string<Form> $class Form class to build.
     * @param array<string, mixed> $values Field values keyed by field name.
     * @param bool $submit Whether to mark the form as submitted.
     * @return Form The submitted form.
     *
     * @throws InvalidArgumentException When the class is not a form.
     */
    function form(string $class, array $values = [], bool $submit = true): Form
    {
        if (!is_subclass_of($class, Form::class)) {
            throw new InvalidArgumentException(sprintf(
                'Form class [%s] must extend %s.',
                $class,
                Form::class,
            ));
        }

        $form = function_exists('app') ? app($class) : new $class();

        return $submit ? $form->submit($values) : $form->setValues($values);
    }
}

if (!function_exists('form_check') && function_exists('helper_add') && helper_add('form_check')) {
    /**
     * Validates a plain array of values against a rules definition.
     *
     * A thin shim over the rules engine's {@see check()}, kept so existing
     * callers keep their helper surface. Useful when there is no form class to
     * hand, such as validating a config array. Returns the failure messages
     * keyed by field name.
     *
     * Example:
     * ```php
     * $errors = form_check(['age' => 12], ['age' => 'required|integer|min:18']);
     * ```
     *
     * @param array<string, mixed> $values Field values keyed by field name.
     * @param array<string, string|list<string>> $rules Rules keyed by field name.
     * @return array<string, list<string>> Failure messages keyed by field name.
     */
    function form_check(array $values, array $rules): array
    {
        return check($values, $rules);
    }
}