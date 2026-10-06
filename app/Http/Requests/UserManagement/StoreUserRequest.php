<?php

namespace App\Http\Requests\UserManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
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
                'unique:users,email',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
            ],
            'position_id' => [
                'required',
                'exists:positions,id',
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
            'shift_type' => [
                'nullable',
                'string',
            ],
            'start_time' => [
                'nullable'
            ],
            'end_time' => [
                'nullable'
            ],
            'immediate_sup_id' => [
                'nullable',
                'exists:users,id',
                'different:id', 
            ],
            'employee_leave_type' => [
                'nullable'
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'position_id' => 'position',
            'role_id' => 'role',
            'team_id' => 'team',
            'shift_type' => 'shift',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'immediate_sup_id' => 'immediate supervisor',
            'employee_leave_type' => 'employee leave type',
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'The name must be at least 2 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'Password must be at least 8 characters long.',
            'position_id.required' => 'Please select a position for this user.',
            'position_id.exists' => 'The selected position does not exist.',
            'role_id.required' => 'Please select a role for this user.',
            'role_id.exists' => 'The selected role does not exist.',
            'immediate_sup_id.exists' => 'The selected supervisor does not exist.',
            'immediate_sup_id.different' => 'A user cannot be their own supervisor.',
            'team_id.exists' => 'The selected team does not exist.',
            'shift_type.exists' => 'The selected shift does not exist.',
            'start_time.exists' => 'Start time does not exist.',
            'end_time.exists' => 'End time does not exist.',
            'employee_leave_type.exists' => 'Leave type does not exist.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('status')) {
            $this->merge([
                'status' => filter_var($this->status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }

        $this->merge([
            'password' => $this->password ?: null,
            'position_id' => $this->position_id ?: null,
            'role_id' => $this->role_id ?: null,
            'team_id' => $this->team_id ?: null,
            'shift_type' => $this->shift_type ?: null,
            'start_time' => $this->start_time ?: null,
            'end_time' => $this->end_time ?: null,
            'immediate_sup_id' => $this->immediate_sup_id ?: null,
            'employee_leave_type' => $this->employee_leave_type ?: null,
        ]);
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->immediate_sup_id) {
                if ($this->wouldCreateCircularHierarchy($this->immediate_sup_id)) {
                    $validator->errors()->add(
                        'immediate_sup_id',
                        'This supervisor assignment would create a circular hierarchy.'
                    );
                }
            }
        });
    }

    private function wouldCreateCircularHierarchy(int $supervisorId): bool
    {
        $supervisor = \App\Models\User::find($supervisorId);

        if (!$supervisor || !$supervisor->status) {
            return true;
        }

        return false;
    }
}