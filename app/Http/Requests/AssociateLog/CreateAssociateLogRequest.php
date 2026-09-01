<?php

namespace App\Http\Requests\AssociateLog;

use Illuminate\Foundation\Http\FormRequest;

class CreateAssociateLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'entry_details' => 'required|string|max:1000',
            'date' => 'required|date',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // 5MB max
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'Please select at least one user.',
            'user_ids.array' => 'Invalid user selection format.',
            'user_ids.min' => 'Please select at least one user.',
            'user_ids.*.exists' => 'One or more selected users do not exist.',
            'entry_details.required' => 'Entry details are required.',
            'entry_details.max' => 'Entry details cannot exceed 1000 characters.',
            'date.required' => 'Date is required.',
            'date.date' => 'Please provide a valid date.',
            'attachment.mimes' => 'Attachment must be a PDF, Word document, or image file.',
            'attachment.max' => 'Attachment size cannot exceed 5MB.',
        ];
    }
}