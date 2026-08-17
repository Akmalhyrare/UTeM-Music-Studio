<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use App\Rules\ValidEmailDomain;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', new ValidEmailDomain(), 'max:100', 'unique:students,email'],
            'matric_no' => ['required', 'string', 'max:20', 'unique:students,matric_no'],
            'phone_no'  => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'password'  => ['required', 'confirmed', new StrongPassword()],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Please enter your full name.',
            'full_name.max'      => 'Your full name may not be longer than 100 characters.',

            'email.required' => 'Please enter your email address.',
            'email.email'    => 'Please enter a valid email address.',
            'email.max'      => 'Your email may not be longer than 100 characters.',
            'email.unique'   => 'An account with this email address already exists.',

            'matric_no.required' => 'Please enter your matric number.',
            'matric_no.max'      => 'Your matric number may not be longer than 20 characters.',
            'matric_no.unique'   => 'An account with this matric number already exists.',

            'phone_no.regex' => 'Please enter a valid phone number (digits, spaces, +, - and () only).',
            'phone_no.max'   => 'Your phone number may not be longer than 20 characters.',

            'password.required'  => 'Please enter a password.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
