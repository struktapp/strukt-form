<?php

namespace Strukt\Form\Fixtures;

use Strukt\Form\Form;

/**
 * A registration form used to check the behaviour of a real form definition.
 */
final class UserForm extends Form
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min_length:2|max_length:50',
            'email' => 'required|email|max_length:120',
            'password' => 'required|string|min_length:8',
            'password_confirmation' => 'required|same:password',
            'age' => 'nullable|integer|min:18|max:120',
            'role' => 'required|in:admin,editor,viewer',
            'terms' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'We need an email address to reach you.',
            'terms.required' => 'You must accept the terms to continue.',
            'min_length' => ':field is too short, use at least :0 characters.',
        ];
    }
}
