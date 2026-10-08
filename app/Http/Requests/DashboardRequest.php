<?php

namespace App\Http\Requests;

class DashboardRequest extends ListRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'sales_period' => ['nullable', 'in:daily,weekly,monthly'],
            'sales_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
