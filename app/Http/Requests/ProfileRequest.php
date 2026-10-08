<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'mobile' => ['required', 'regex:/^[+0-9 ()-]{7,25}$/'], 'current_password' => ['required', 'current_password'], 'password' => [$this->user()->must_change_password ? 'required' : 'nullable', 'confirmed', 'different:current_password', Password::defaults(), 'regex:/[A-Z]/', 'max:255']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
