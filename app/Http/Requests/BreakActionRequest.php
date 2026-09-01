<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BreakActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_\-\s]+$/', // Allow alphanumeric, underscore, hyphen, and spaces
            ],
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Break type is required.',
            'type.string' => 'Break type must be a string.',
            'type.max' => 'Break type cannot exceed 50 characters.',
            'type.regex' => 'Break type can only contain letters, numbers, spaces, hyphens, and underscores.',
            'notes.max' => 'Break notes cannot exceed 500 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'break type',
            'notes' => 'break notes',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('type')) {
            $this->merge([
                'type' => strtolower(trim($this->input('type')))
            ]);
        }

        if ($this->has('notes')) {
            $this->merge([
                'notes' => trim(preg_replace('/\s+/', ' ', $this->input('notes')))
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('type');
            
            if ($type) {
                $reservedWords = ['admin', 'system', 'delete', 'null', 'undefined'];
                
                if (in_array(strtolower($type), $reservedWords)) {
                    $validator->errors()->add('type', 'This break type name is not allowed.');
                }
                
                if (strlen(trim($type)) < 2) {
                    $validator->errors()->add('type', 'Break type must be at least 2 characters long.');
                }
            }
        });
    }
}