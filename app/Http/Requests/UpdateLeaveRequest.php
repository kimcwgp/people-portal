<?php

namespace App\Http\Requests;

use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $leave = $this->route('leave');
        return $leave && $leave->user_id === auth()->id();
    }

    public function rules(): array
    {
        $rules = [
            'leaves_type_id' => ['required','exists:leaves_type,id'],
            'start_date'     => ['required','date'],
            'end_date'       => ['required','date','after_or_equal:start_date'],
            'duration'       => ['required', Rule::in(['All Day','Half Day (8am to 12nn)','Half Day (1pm to 5pm)','Custom'])],
            'time_in'        => ['required_if:duration,Custom','nullable','date_format:H:i'],
            'time_out'       => ['required_if:duration,Custom','nullable','date_format:H:i','after:time_in'],
            'reason'         => ['required','string','max:1000'],
            'attachment'     => ['nullable','file','mimes:pdf,jpg,jpeg,png,doc,docx','max:2048'],
        ];

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $typeId = $this->input('leaves_type_id');
            $leave = $this->route('leave');

            if ($typeId) {
                $leaveType = LeaveType::find($typeId);
                $requiresMC = $leaveType && str_contains($leaveType->name, 'WITH Medical Certificate');

                if ($requiresMC && empty($leave->attachment) && !$this->hasFile('attachment')) {
                    $validator->errors()->add('attachment', 'Medical certificate is required for this leave type.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'time_out.after'        => 'End time must be after start time.',
            'time_in.required_if'   => 'Start time is required when duration is Custom.',
            'time_out.required_if'  => 'End time is required when duration is Custom.',
            'duration.in'           => 'Please select a valid duration option.',
            'end_date.after_or_equal'   => 'End date must be on or after start date.',
            'attachment.required'       => 'Medical certificate is required for this leave type.',
            'attachment.max'            => 'File size must be less than 2MB.',
            'attachment.mimes'          => 'Please upload PDF, JPG, PNG, DOC, or DOCX files only.',
        ];
    }
}