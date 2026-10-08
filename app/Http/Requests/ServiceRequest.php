<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150', Rule::unique('services')->ignore($this->route('service'))], 'price' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'], 'active' => ['required', 'boolean']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
