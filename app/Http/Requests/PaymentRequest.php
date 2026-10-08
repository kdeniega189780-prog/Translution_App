<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['payment_method' => ['required', 'in:Cash,Bank Transfer,GCash,PayMaya']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
