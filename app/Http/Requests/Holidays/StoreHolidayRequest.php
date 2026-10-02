<?php

namespace App\Http\Requests\Holidays;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'calendar' => ['required', Rule::in(['partners', 'people'])],
            'type' => ['required', Rule::in(['regular', 'special_non_working', 'company'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'calendar.in' => 'Calendar must be either Partners or People.',
        ];
    }
}
