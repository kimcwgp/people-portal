<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // User can update their own profile
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'position_name' => 'sometimes|required|string|max:150',
            
            // Personal Information validation
            'personal_info.date_of_birth' => 'sometimes|nullable|date|before:today',
            'personal_info.gender' => 'sometimes|nullable|in:male,female',
            'personal_info.marital_status' => 'sometimes|nullable|in:single,married,divorced,widowed',
            'personal_info.spouse_name' => 'sometimes|nullable|string|max:255',
            'personal_info.num_children' => 'sometimes|nullable|integer|min:0|max:20',
            'personal_info.phone_number' => 'sometimes|nullable|string|max:20',
            'personal_info.alternate_phone_number' => 'sometimes|nullable|string|max:20',
            'personal_info.permanent_address' => 'sometimes|nullable|string|max:500',
            'personal_info.current_address' => 'sometimes|nullable|string|max:500',
            'personal_info.emergency_contact_name' => 'sometimes|nullable|string|max:255',
            'personal_info.emergency_contact_number' => 'sometimes|nullable|string|max:20',
            'personal_info.emergency_contact_relationship' => 'sometimes|nullable|string|max:50',
            'personal_info.tin' => 'sometimes|nullable|string|max:50',
            'personal_info.sss' => 'sometimes|nullable|string|max:50',
            'personal_info.philhealth' => 'sometimes|nullable|string|max:50',
            'personal_info.pagibig' => 'sometimes|nullable|string|max:50',

            // Employment Information validation
            'employment_info.employee_id' => 'sometimes|nullable|string|max:20',
            'employment_info.hire_date' => 'sometimes|nullable|date',
            'employment_info.regularization_date' => 'sometimes|nullable|date|after_or_equal:employment_info.hire_date',
            'employment_info.position_level' => 'sometimes|nullable|string|max:100',
            'employment_info.career_level' => 'sometimes|nullable|string|max:100',
            'employment_info.career_band' => 'sometimes|nullable|string|max:100',
            'employment_info.career_zone' => 'sometimes|nullable|string|max:100',
            'employment_info.employment_type' => 'sometimes|nullable|in:full_time,part_time,contract,intern,consultant',

            // Associate Log validation (for HR/Admin only)
            'associate_log.entry_details' => 'sometimes|required_with:associate_log|string|max:1000',
            'associate_log.date' => 'sometimes|required_with:associate_log|date',
            'associate_log.attachments' => 'sometimes|nullable|array|max:5',
            'associate_log.attachments.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif|max:10240',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'personal_info.date_of_birth.before' => 'Date of birth must be before today.',
            'personal_info.gender.in' => 'Please select a valid gender.',
            'personal_info.marital_status.in' => 'Please select a valid marital status.',
            'personal_info.num_children.min' => 'Number of children cannot be negative.',
            'personal_info.num_children.max' => 'Number of children cannot exceed 20.',
            
            'employment_info.regularization_date.after_or_equal' => 'Regularization date must be after or equal to hire date.',
            'employment_info.employment_type.in' => 'Please select a valid employment type.',

            // Associate log messages
            'associate_log.entry_details.required_with' => 'Entry details are required when creating a log.',
            'associate_log.entry_details.max' => 'Entry details cannot exceed 1000 characters.',
            'associate_log.date.required_with' => 'Date is required when creating a log.',
            'associate_log.date.date' => 'Please provide a valid date.',
            'associate_log.attachments.max' => 'You can upload a maximum of 5 files.',
            'associate_log.attachments.*.file' => 'Each attachment must be a valid file.',
            'associate_log.attachments.*.mimes' => 'Attachments must be: PDF, DOC, DOCX, JPG, JPEG, PNG, or GIF.',
            'associate_log.attachments.*.max' => 'Each file cannot exceed 10MB.',
        
        ];
    }
}