<?php

namespace App\Http\Requests\LeaveTypes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $leaveTypeId = $this->route('leave_type')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('leaves_type', 'name')->ignore($leaveTypeId)
            ],
            'type' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Leave type name is required',
            'name.unique' => 'This leave type already exists',
            'type.required' => 'Leave type code is required',
        ];
    }
}