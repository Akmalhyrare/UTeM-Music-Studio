<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'studio_id'    => 'required|integer|exists:studios,studio_id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_time'   => 'required|date_format:H:i|after_or_equal:08:00|before:23:30',
            'end_time'     => 'required|date_format:H:i|after:start_time|before_or_equal:23:30',
            'purpose'      => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'The end time must be after the start time.',
            'booking_date.after_or_equal' => 'The booking date must be today or a future date.',
            'start_time.after_or_equal' => 'Bookings can only start at or after 08:00.',
            'start_time.before' => 'Bookings can only start before 23:30.',
            'end_time.before_or_equal' => 'Bookings must end by 23:30.',
        ];
    }
}
