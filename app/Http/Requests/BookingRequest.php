<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['vehicle_id' => ['required', 'integer', 'exists:vehicles,id'], 'service_id' => ['required', 'integer', 'exists:services,id'], 'preferred_at' => ['required', 'date', 'after_or_equal:today'], 'problem' => ['required', 'string', 'max:2000'], 'notes' => ['nullable', 'string', 'max:2000'], 'pickup_requested' => ['sometimes', 'boolean'], 'source' => ['nullable', 'in:online,walk-in,phone,messenger']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
