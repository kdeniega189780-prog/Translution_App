<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', 'in:pending,scheduled,processing,completed,released'],
            'receive' => ['nullable', 'in:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'movements_page' => ['nullable', 'integer', 'min:1'],
            'recent_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
