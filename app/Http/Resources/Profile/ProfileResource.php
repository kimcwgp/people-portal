<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'online' => $this->online,
            'position_name' => $this->currentJobInformation?->position_name,
            'employment_type' => $this->employee?->employment_type,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Roles and permissions
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            
            // Personal Information
            'personal_info' => $this->formatPersonalInfo(),
            
            // Employment Details
            'employment_info' => $this->formatEmploymentInfo(),
            
            // Shift Information
            'shift' => $this->when($this->shift, fn() => [
                'id' => $this->shift->id,
                'shift_type' => $this->shift->shift_type,
                'start_time' => $this->shift->start_time?->format('g:i A'),
                'end_time' => $this->shift->end_time?->format('g:i A'),
                'shift_label' => $this->shift->shift_label,
            ]),
            
            // Associate Logs (only when loaded)
            'associate_logs' => $this->when(
                $this->relationLoaded('associateLogs'),
                fn() => $this->associateLogs->map(fn($log) => [
                    'id' => $log->id,
                    'entry_details' => $log->entry_details,
                    'date' => $log->date?->format('M. d, Y'),
                    'created_by' => $log->creator?->name ?? 'System',
                    'created_at' => $log->created_at?->toISOString(),
                    'attachments' => $log->hasAttachment() ? [[
                        'id' => $log->id,
                        'filename' => $log->attachment_name,
                        'file_type' => $log->getFileExtension(),
                        'download_url' => route('user.profile.download-attachment', $log->id),
                    ]] : [],
                ])
            ),
            
            // Computed values
            'status_label' => $this->status ? 'Active' : 'Inactive',
            'online_label' => $this->online ? 'Online' : 'Offline',
            'employment_type_label' => $this->formatEmploymentTypeLabel(),
            'position_label' => $this->currentJobInformation?->position_name ?: 'No Position Assigned',
            'initials' => $this->generateInitials(),
        ];
    }

    /**
     * Format personal information
     */
    private function formatPersonalInfo(): array
    {
        $personal = $this->personalInformation;

        return [
            'date_of_birth' => $personal?->date_of_birth?->format('Y-m-d'),
            'gender' => $personal?->gender,
            'marital_status' => $personal?->marital_status,
            'spouse_name' => $personal?->spouse_name,
            'num_children' => $personal?->num_children ?? 0,
            'phone_number' => $personal?->phone_number,
            'alternate_phone_number' => $personal?->alternate_phone_number,
            'permanent_address' => $personal?->permanent_address,
            'current_address' => $personal?->current_address,
            'emergency_contact_name' => $personal?->emergency_contact_name,
            'emergency_contact_number' => $personal?->emergency_contact_number,
            'emergency_contact_relationship' => $personal?->emergency_contact_relationship,
            'tin' => $personal?->tin,
            'sss' => $personal?->sss,
            'philhealth' => $personal?->philhealth,
            'pagibig' => $personal?->pagibig,
        ];
    }

    /**
     * Format employment information
     */
    private function formatEmploymentInfo(): array
    {
        $employee = $this->employee;
        $job = $this->currentJobInformation;

        return [
            'employee_id' => $employee?->employee_id,
            'hire_date' => $employee?->hire_date?->format('Y-m-d'),
            'regularization_date' => $employee?->regularization_date?->format('Y-m-d'),
            'employment_status' => $employee?->employment_status,
            'employment_type' => $employee?->employment_type,
            'position_level' => $job?->position_level,
            'career_level' => $job?->career_level,
            'career_band' => $job?->career_band,
            'career_zone' => $job?->career_zone,
        ];
    }

    /**
     * Format employment type for display
     */
    private function formatEmploymentTypeLabel(): string
    {
        $employmentType = $this->employee?->employment_type;

        if (!$employmentType) {
            return 'Not Set';
        }
        
        return match($employmentType) {
            'full_time' => 'Full Time',
            'part_time' => 'Part Time',
            'contract' => 'Contract',
            'intern' => 'Intern',
            'consultant' => 'Consultant',
            default => ucfirst(str_replace('_', ' ', $employmentType))
        };
    }

    /**
     * Generate user initials from name
     */
    private function generateInitials(): string
    {
        $words = explode(' ', trim($this->name));
        
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        
        return strtoupper(substr($this->name, 0, 2));
    }
}
