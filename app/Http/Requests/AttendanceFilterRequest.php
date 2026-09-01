<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date|after_or_equal:startDate',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:2020,' . date('Y'),
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|between:1,100',
            'user_name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->input('page', 1),
        ]);
    }

    public function messages(): array
    {
        return [
            'endDate.after_or_equal' => 'End date must be after or equal to start date.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'month.between' => 'Month must be between 1 and 12.',
            'per_page.between' => 'Records per page must be between 1 and 100.',
            'per_page.max' => 'Maximum 100 records per page allowed.',
            'user_name.max' => 'User name must not exceed 255 characters.',
        ];
    }
}