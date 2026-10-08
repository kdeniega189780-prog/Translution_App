<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsePartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['part_id' => ['required', 'integer', 'exists:parts,id'], 'quantity' => ['required', 'integer', 'min:1', 'max:10000']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
