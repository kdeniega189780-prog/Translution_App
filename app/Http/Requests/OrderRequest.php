<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['supplier' => ['required', 'string', 'max:150'], 'items' => ['required', 'array', 'min:1', 'max:50'], 'items.*.part_id' => ['required', 'integer', 'distinct', 'exists:parts,id'], 'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'], 'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
