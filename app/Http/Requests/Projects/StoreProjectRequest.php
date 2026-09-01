<?php

namespace App\Http\Requests\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'project_name' => 'required|string|max:255',
            'project_type_id' => 'required|exists:project_types,id', 
            'pm_id' => 'nullable|exists:users,id',
            'glip_url' => 'nullable|url|max:500',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'The selected client is invalid.',
            'project_name.required' => 'Project name is required.',
            'project_name.max' => 'Project name cannot exceed 255 characters.',
            'glip_url.url' => 'Please enter a valid Glip URL.',
            'pm_id.exists' => 'The selected project manager is invalid.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }
}