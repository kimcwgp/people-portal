<?php

namespace App\Http\Requests\Overtime;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StoreOvertimeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'nullable',
                'exists:projects,id',
            ],
            'project_manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('team_id', 6)->where('status', 1);
                }),
            ],
            'ot_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'time_in' => [
                'required',
                'date_format:H:i',
            ],
            'time_out' => [
                'required',
                'date_format:H:i',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'ot_date' => 'overtime date',
            'project_id' => 'project',
            'project_manager_id' => 'project manager',
            'time_in' => 'time in',
            'time_out' => 'time out',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'ot_date.before_or_equal' => 'Overtime date cannot be in the future.',
            'time_out.after' => 'Time out must be after time in.',
            'project_id.exists' => 'The selected project is invalid.',
            'project_manager_id.exists' => 'The selected project manager must be from the Project Management team and active.',
        ];
    }
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty strings to null
        if ($this->notes === '') {
            $this->merge(['notes' => null]);
        }

        // Set default overtime date if not provided
        if (!$this->ot_date) {
            $this->merge(['ot_date' => now()->format('Y-m-d')]);
        }
    }

    /**
     * Get validated data with additional processing.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        
        // Add user_id
        $validated['user_id'] = auth()->id();
        
        // Trim whitespace from notes
        if (isset($validated['notes'])) {
            $validated['notes'] = trim($validated['notes']) ?: null;
        }
        
        return $validated;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validate time duration (minimum 1 hour, maximum 12 hours)
            if ($this->time_in && $this->time_out) {
                $timeIn = Carbon::createFromFormat('H:i', $this->time_in);
                $timeOut = Carbon::createFromFormat('H:i', $this->time_out);
                
                $hours = $timeOut->diffInHours($timeIn, true);
                
                if ($hours < 1) {
                    $validator->errors()->add('time_out', 'Overtime must be at least 1 hour.');
                }
                
                if ($hours > 12) {
                    $validator->errors()->add('time_out', 'Overtime cannot exceed 12 hours per day.');
                }
            }
        });
    }
}