<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user'))], 'mobile' => ['required', 'regex:/^[+0-9 ()-]{7,25}$/'], 'role' => ['required', 'in:secretary,mechanic,purchaser'], 'active' => ['required', 'boolean'], 'password' => [$this->route('user') ? 'nullable' : 'required', 'confirmed', Password::defaults(), 'regex:/[A-Z]/', 'max:255']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
