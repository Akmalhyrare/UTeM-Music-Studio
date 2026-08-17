<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'studio_name' => 'required|string|max:100',
            'studio_type' => 'required|string|max:50',
            'description' => 'nullable|string',
            'capacity'    => 'nullable|integer|min:1',
            'equipment'   => 'nullable|string',
            'location'    => 'nullable|string|max:150',
            'size'        => 'nullable|string|max:50',
            'status'      => 'required|in:available,maintenance,blocked,inactive',
        ];
    }
}
