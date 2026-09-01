<?php

namespace App\Http\Requests\Standup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStandupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $standup = $this->route('standup');
        return auth()->check() && $standup->user_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            'project_id' => [
                'nullable',
                'exists:projects,id',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'impediments' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'hours' => [
                'nullable',
                'integer',
                'min:0',
                'max:24',
            ],
            'minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:59',
            ],
            'standup_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'project_id' => 'project',
            'notes' => 'notes',
            'impediments' => 'impediments',
            'hours' => 'hours',
            'minutes' => 'minutes',
            'standup_date' => 'standup date',
        ];
    }

    public function messages(): array
    {
        return [
            'standup_date.before_or_equal' => 'Standup date cannot be in the future.',
            'project_id.exists' => 'The selected project is invalid.',
            'notes.max' => 'Notes cannot exceed :max characters.',
            'impediments.max' => 'Impediments cannot exceed :max characters.',
            'hours.max' => 'Hours cannot exceed 24.',
            'minutes.max' => 'Minutes cannot exceed 59.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'project_id' => empty($this->project_id) ? null : $this->project_id,
            'notes' => empty($this->notes) ? null : trim($this->notes),
            'impediments' => empty($this->impediments) ? null : trim($this->impediments),
            'hours' => isset($this->hours) ? (int)$this->hours : 0,
            'minutes' => isset($this->minutes) ? (int)$this->minutes : 0,
        ]);
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        
        $totalMinutes = ($validated['hours'] ?? 0) * 60 + ($validated['minutes'] ?? 0);
        $validated['time_spent_minutes'] = $totalMinutes;
        
        unset($validated['hours'], $validated['minutes']);
        
        return $validated;
    }
}