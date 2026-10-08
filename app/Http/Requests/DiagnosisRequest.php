<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['diagnosis' => ['required', 'string', 'max:4000'], 'service_price' => [$this->user()->role === 'admin' ? 'nullable' : 'prohibited', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
