<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['customer_id' => [$this->user()->role === 'customer' ? 'prohibited' : 'required', 'integer', 'exists:customers,id'], 'plate_number' => ['required', 'string', 'max:20', Rule::unique('vehicles')->ignore($this->route('vehicle'))], 'make' => ['required', 'string', 'max:80'], 'model' => ['required', 'string', 'max:80'], 'year' => ['required', 'integer', 'min:1950', 'max:'.(date('Y') + 1)], 'transmission' => ['required', 'in:Automatic,Manual,CVT,DCT']];
    }

    public function messages(): array
    {
        return ['password.regex' => 'Use at least one uppercase letter.', 'mobile.regex' => 'Enter a valid mobile number.'];
    }
}
