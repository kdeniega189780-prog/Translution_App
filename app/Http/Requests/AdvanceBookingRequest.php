<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdvanceBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['expected_status' => ['required', 'in:pending,scheduled,processing'], 'scheduled_at' => ['required_if:expected_status,pending', 'nullable', 'date', 'after_or_equal:today'], 'assigned_staff_id' => ['required_if:expected_status,scheduled', 'nullable', 'integer', 'exists:users,id']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
