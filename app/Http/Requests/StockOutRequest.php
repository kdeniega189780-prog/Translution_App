<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin', 'purchaser');
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'reason' => ['required', 'string', 'max:255'],
            'expected_quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}
