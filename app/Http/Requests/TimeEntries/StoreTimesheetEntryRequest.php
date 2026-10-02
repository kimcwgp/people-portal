<?php

namespace App\Http\Requests\TimeEntries;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimesheetEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hours = ['nullable', 'numeric', 'min:0', 'max:24'];

        return [
            'project_id' => ['nullable', 'exists:projects,id'],
            'project_ticket' => ['nullable', 'string', 'max:100'],
            'time_type_id' => ['required', 'exists:time_types,id'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'mon_hours' => $hours, 'tue_hours' => $hours, 'wed_hours' => $hours,
            'thu_hours' => $hours, 'fri_hours' => $hours, 'sat_hours' => $hours,
            'sun_hours' => $hours,
        ];
    }

    public function messages(): array
    {
        return [
            'time_type_id.required' => 'Pick a time type for this line.',
            '*.max' => 'A single day cannot exceed 24 hours.',
        ];
    }
}
