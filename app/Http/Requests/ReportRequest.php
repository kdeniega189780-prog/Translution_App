<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'in:daily,weekly,monthly'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'format' => ['nullable', 'in:csv,pdf'],
            'sales_page' => ['nullable', 'integer', 'min:1'],
            'stock_page' => ['nullable', 'integer', 'min:1'],
            'activity_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
