<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudioUnavailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'     => 'required|in:maintenance,blocked',
            'start_at' => 'required|date',
            'end_at'   => 'required|date|after:start_at',
            'reason'   => 'nullable|string|max:255',
        ];
    }
}
