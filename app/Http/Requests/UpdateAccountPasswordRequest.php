<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(session('user_type'), ['staff', 'student']);
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'          => ['required', 'confirmed', new StrongPassword()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Please enter your current password.',
            'password.required'         => 'Please enter a new password.',
            'password.confirmed'        => 'The new password confirmation does not match.',
        ];
    }
}
