<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'            => ['nullable', 'in:bookings,borrowings,inventory,utilization,maintenance,pending-queue,user-engagement,collections'],
            'date_from'       => ['nullable', 'date'],
            'date_to'         => ['nullable', 'date', 'after_or_equal:date_from'],
            'status'          => ['nullable', 'string'],
            'studio_id'       => ['nullable', 'integer', 'exists:studios,studio_id'],
            'category_id'     => ['nullable', 'integer', 'exists:categories,category_id'],
            'student_id'      => ['nullable', 'integer', 'exists:students,student_id'],
            'operating_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'overdue_days'    => ['nullable', 'integer', 'min:1', 'max:90'],
            'overdue_only'    => ['nullable', 'in:0,1'],
            'days'            => ['nullable', 'integer', 'in:7,14,30,90'],
        ];
    }
}
