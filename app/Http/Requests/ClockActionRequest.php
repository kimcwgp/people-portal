<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClockActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'action_type' => 'nullable|string|in:time_in,time_out',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'location.max' => 'Location cannot exceed 255 characters.',
            'action_type.in' => 'Action type must be either time_in or time_out.',
        ];
    }

    public function attributes(): array
    {
        return [
            'notes' => 'daily notes',
            'location' => 'location',
            'action_type' => 'action type',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $this->merge([
                'notes' => trim(preg_replace('/\s+/', ' ', $this->input('notes')))
            ]);
        }
    }
}