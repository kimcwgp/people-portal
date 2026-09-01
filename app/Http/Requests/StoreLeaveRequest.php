<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        // Allow filing leaves up to 30 days in the past for emergencies/sickness
        $thirtyDaysAgo = now()->subDays(30)->format('Y-m-d');
        
        return [
            'leaves_type_id' => 'required|exists:leaves_type,id',
            'start_date'     => "required|date|after_or_equal:{$thirtyDaysAgo}",
            'end_date'       => 'required|date|after_or_equal:start_date',
            'duration'       => 'required|in:All Day,Half Day (8am to 12nn),Half Day (1pm to 5pm),Custom',
            'time_in'        => 'required_if:duration,Custom|nullable|date_format:H:i',
            'time_out'       => 'required_if:duration,Custom|nullable|date_format:H:i|after:time_in',
            'reason'         => 'required|string|max:1000',
            'attachment'     => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'You can only file leaves up to 30 days in the past.',
            'time_out.after'        => 'End time must be after start time.',
            'time_in.required_if'   => 'Start time is required when duration is Custom.',
            'time_out.required_if'  => 'End time is required when duration is Custom.',
            'duration.in'           => 'Please select a valid duration option.',
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        $validated['user_id'] = auth()->id();
        return $validated;
    }
}
