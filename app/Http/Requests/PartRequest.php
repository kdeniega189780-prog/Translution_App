<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150', Rule::unique('parts')->ignore($this->route('part'))], 'category' => ['required', 'string', 'max:80'], 'unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'], 'reorder_level' => ['required', 'integer', 'min:0', 'max:100000'], 'supplier' => ['required', 'string', 'max:150']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
