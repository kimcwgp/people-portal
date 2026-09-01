<?php

namespace App\Http\Requests\Shifts;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShiftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'shift_type' => 'required|in:day,night',
            'start_time' => 'required|date_format:H:i',
            'end_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $shiftType = $this->input('shift_type');
                    $startTime = $this->input('start_time');
                    
                    if ($shiftType === 'day' && $value <= $startTime) {
                        $fail('End time must be after start time for day shifts.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'shift_type.required' => 'Shift type is required.',
            'shift_type.in' => 'Shift type must be either day or night.',
            'start_time.required' => 'Start time is required.',
            'start_time.date_format' => 'Start time must be in HH:MM format (e.g., 08:00).',
            'end_time.required' => 'End time is required.',
            'end_time.date_format' => 'End time must be in HH:MM format (e.g., 17:00).',
            'end_time.after' => 'End time must be after start time.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'shift_type' => 'shift type',
            'start_time' => 'start time',
            'end_time' => 'end time',
        ];
    }
}
