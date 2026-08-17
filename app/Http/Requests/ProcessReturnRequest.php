<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'returns'                       => 'required|array|min:1',
            'returns.*.item_id'             => 'required|integer|exists:borrowing_details,item_id',
            'returns.*.quantity_returned'   => 'required|integer|min:0',
            'returns.*.item_condition'      => 'required|in:good,damaged,lost',
            'returns.*.damage_note'         => 'nullable|string|max:255',
        ];
    }
}
