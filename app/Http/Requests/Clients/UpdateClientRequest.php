<?php

namespace App\Http\Requests\Clients;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_company' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Client name is required',
            'name.max' => 'Client name must not exceed 255 characters',
            'parent_company.max' => 'Parent company name must not exceed 255 characters',
            'contact_name.max' => 'Contact name must not exceed 255 characters',
            'contact_number.max' => 'Contact number must not exceed 50 characters',
            'description.max' => 'Description must not exceed 1000 characters',
        ];
    }
}
