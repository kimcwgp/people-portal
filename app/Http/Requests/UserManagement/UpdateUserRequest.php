<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id ?? $this->route('user');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'glip_url' => [
                'nullable',
                'url',
                'max:500',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
            ],
            'role_id' => [
                'required',
                'exists:roles,id',
            ],
            'status' => [
                'required',
                'boolean',
            ],
            'team_id' => [
                'nullable',
                'exists:teams,id',
            ],
            'shift_id' => [
                'nullable',
                'exists:shifts,id',
            ],
            'immediate_sup_id' => [
                'nullable',
                'exists:users,id',
                Rule::notIn([$userId]), // Cannot be their own supervisor
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'glip_url' => 'Glip webhook URL',
            'role_id' => 'role',
            'team_id' => 'team',
            'shift_id' => 'shift',
            'immediate_sup_id' => 'immediate supervisor',
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'The name must be at least 2 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'Password must be at least 8 characters long.',
            'glip_url.url' => 'Please provide a valid URL for the Glip webhook.',
            'role_id.required' => 'Please select a role for this user.',
            'role_id.exists' => 'The selected role does not exist.',
            'immediate_sup_id.exists' => 'The selected supervisor does not exist.',
            'immediate_sup_id.not_in' => 'A user cannot be their own supervisor.',
            'team_id.exists' => 'The selected team does not exist.',
            'shift_id.exists' => 'The selected shift does not exist.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize boolean values
        if ($this->has('status')) {
            $this->merge([
                'status' => filter_var($this->status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }

        // Clean up empty values
        $this->merge([
            'glip_url' => $this->glip_url ?: null,
            'password' => $this->password ?: null,
            'role_id' => $this->role_id ?: null,
            'team_id' => $this->team_id ?: null,
            'shift_id' => $this->shift_id ?: null,
            'immediate_sup_id' => $this->immediate_sup_id ?: null,
        ]);
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            $userId = $this->route('user')->id ?? $this->route('user');
            
            // Prevent deactivating own account
            if ($userId == auth()->id() && !$this->status) {
                $validator->errors()->add('status', 'You cannot deactivate your own account.');
            }

            // Validate circular supervisor relationship
            if ($this->immediate_sup_id) {
                if ($this->wouldCreateCircularHierarchy($userId, $this->immediate_sup_id)) {
                    $validator->errors()->add(
                        'immediate_sup_id',
                        'This supervisor assignment would create a circular hierarchy.'
                    );
                }
            }
        });
    }

    private function wouldCreateCircularHierarchy(int $userId, int $supervisorId): bool
    {
        // Get the supervisor
        $supervisor = \App\Models\User::find($supervisorId);
        
        if (!$supervisor || !$supervisor->status) {
            return true; // Inactive supervisor
        }

        // Check if the supervisor (or any supervisor up the chain) reports to this user
        $currentSupervisor = $supervisor;
        $visitedIds = [];
        
        while ($currentSupervisor && $currentSupervisor->immediate_sup_id) {
            // Prevent infinite loops
            if (in_array($currentSupervisor->immediate_sup_id, $visitedIds)) {
                break;
            }
            
            $visitedIds[] = $currentSupervisor->immediate_sup_id;
            
            // If supervisor's chain leads back to this user, it's circular
            if ($currentSupervisor->immediate_sup_id == $userId) {
                return true;
            }
            
            $currentSupervisor = \App\Models\User::find($currentSupervisor->immediate_sup_id);
        }

        return false;
    }
}