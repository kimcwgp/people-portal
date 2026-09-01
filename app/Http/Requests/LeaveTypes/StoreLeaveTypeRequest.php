<?php

namespace App\Http\Requests\LeaveTypes;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:leaves_type,name',
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