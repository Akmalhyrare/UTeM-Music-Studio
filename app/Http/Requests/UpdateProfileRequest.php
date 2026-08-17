<?php

namespace App\Http\Requests;

use App\Rules\ValidEmailDomain;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(session('user_type'), ['staff', 'student']);
    }

    public function rules(): array
    {
        $userId = session('user_id');

        if (session('user_type') === 'staff') {
            return [
                'full_name' => ['required', 'string', 'max:100'],
                'email'     => ['required', 'email', new ValidEmailDomain(), 'max:100', "unique:staff,email,{$userId},staff_id"],
                'phone_no'  => ['nullable', 'string', 'max:20'],
            ];
        }

        return [
            'full_name' => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', new ValidEmailDomain(), 'max:100', "unique:students,email,{$userId},student_id"],
            'phone_no'  => ['nullable', 'string', 'max:20'],
        ];
    }
}
