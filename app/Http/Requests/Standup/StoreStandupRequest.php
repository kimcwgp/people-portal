<?php

namespace App\Http\Requests\Standup;

use Illuminate\Foundation\Http\FormRequest;

class StoreStandupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'standup_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'standups' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],
            'standups.*.project_id' => [
                'nullable',
                'exists:projects,id',
            ],
            'standups.*.notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'standups.*.impediments' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'standups.*.hours' => [
                'nullable',
                'integer',
                'min:0',
                'max:24',
            ],
            'standups.*.minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:59',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'standup_date' => 'standup date',
            'standups.*.project_id' => 'project',
            'standups.*.notes' => 'notes',
            'standups.*.impediments' => 'impediments',
            'standups.*.hours' => 'hours',
            'standups.*.minutes' => 'minutes',
        ];
    }

    public function messages(): array
    {
        return [
            'standup_date.before_or_equal' => 'Standup date cannot be in the future.',
            'standups.required' => 'At least one standup entry is required.',
            'standups.min' => 'At least one standup entry is required.',
            'standups.max' => 'You cannot create more than :max standups at once.',
            'standups.*.project_id.exists' => 'The selected project is invalid.',
            'standups.*.notes.max' => 'Notes cannot exceed :max characters.',
            'standups.*.impediments.max' => 'Impediments cannot exceed :max characters.',
            'standups.*.hours.max' => 'Hours cannot exceed 24.',
            'standups.*.minutes.max' => 'Minutes cannot exceed 59.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->standup_date) {
            $this->merge(['standup_date' => now()->format('Y-m-d')]);
        }

        if ($this->has('standups') && is_array($this->standups)) {
            $standups = collect($this->standups)->map(function ($standup) {
                return [
                    'project_id' => empty($standup['project_id']) ? null : $standup['project_id'],
                    'notes' => empty($standup['notes']) ? null : trim($standup['notes']),
                    'impediments' => empty($standup['impediments']) ? null : trim($standup['impediments']),
                    'hours' => isset($standup['hours']) ? (int)$standup['hours'] : 0,
                    'minutes' => isset($standup['minutes']) ? (int)$standup['minutes'] : 0,
                ];
            })->filter(function ($standup) {
                return !empty($standup['notes']) || 
                    !empty($standup['impediments']) || 
                    !is_null($standup['project_id']) ||
                    ($standup['hours'] > 0 || $standup['minutes'] > 0);
            })->values()->toArray();

            $this->merge(['standups' => $standups]);
        }
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        
        if (isset($validated['standups'])) {
            $validated['standups'] = collect($validated['standups'])->map(function ($standup) use ($validated) {
                $totalMinutes = ($standup['hours'] ?? 0) * 60 + ($standup['minutes'] ?? 0);
                
                return array_merge($standup, [
                    'standup_date' => $validated['standup_date'],
                    'user_id' => auth()->id(),
                    'time_spent_minutes' => $totalMinutes,
                ]);
            })->toArray();
        }
        
        return $validated;
    }
}