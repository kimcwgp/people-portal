<?php

namespace App\Services;

use App\Models\{User, AssociateLog};
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Http\UploadedFile;

class ProfileService
{
    public function getUserProfile(User $user): User
    {
        return $user->load([
            'personalInformation',
            'employee',
            'currentJobInformation',
            'associateLogs' => function ($query) {
                $query->with('creator:id,name')
                      ->latest()
                      ->limit(50);
            },
            'shift:id,shift_type,start_time,end_time'
        ]);
    }

    public function updateProfile(User $user, array $validated): User
    {
        DB::beginTransaction();
        
        try {
            if (isset($validated['name'])) {
                $user->update(['name' => $validated['name']]);
            }

            if (isset($validated['position_name'])) {
                $this->updatePositionName($user, $validated['position_name']);
            }

            if (isset($validated['personal_info'])) {
                $this->updatePersonalInformation($user, $validated['personal_info']);
            }

            if (isset($validated['employment_info'])) {
                $this->updateEmploymentInformation($user, $validated['employment_info']);
            }

            if (isset($validated['associate_log'])) {
                $this->createAssociateLog($user, $validated['associate_log']);
            }

            DB::commit();

            return $this->getUserProfile($user->fresh());

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function updatePositionName(User $user, string $positionName): void
    {
        if ($user->currentJobInformation) {
            $user->currentJobInformation->update(['position_name' => $positionName]);
        } else {
            $user->jobInformation()->create(['position_name' => $positionName]);
        }
    }

    private function updatePersonalInformation(User $user, array $personalInfo): void
    {
        if ($user->personalInformation) {
            $user->personalInformation->update($personalInfo);
        } else {
            $user->personalInformation()->create($personalInfo);
        }
    }

    private function updateEmploymentInformation(User $user, array $employmentInfo): void
    {
        // Prepare employee data (allow null and empty string to clear fields)
        $employeeData = [];
        
        if (array_key_exists('employee_id', $employmentInfo)) {
            $employeeData['employee_id'] = $employmentInfo['employee_id'];
        }
        if (array_key_exists('hire_date', $employmentInfo)) {
            $employeeData['hire_date'] = $employmentInfo['hire_date'];
        }
        if (array_key_exists('regularization_date', $employmentInfo)) {
            $employeeData['regularization_date'] = $employmentInfo['regularization_date'];
        }
        if (array_key_exists('employment_type', $employmentInfo)) {
            $employeeData['employment_type'] = $employmentInfo['employment_type'];
        }

        if (!empty($employeeData)) {
            if ($user->employee) {
                $user->employee->update($employeeData);
            } else {
                $employeeData['employment_type'] = $employeeData['employment_type'] ?? 'full_time';
                $user->employee()->create($employeeData);
            }
        }

        // Prepare job information data
        $jobData = [];
        
        if (array_key_exists('position_level', $employmentInfo)) {
            $jobData['position_level'] = $employmentInfo['position_level'];
        }
        if (array_key_exists('career_level', $employmentInfo)) {
            $jobData['career_level'] = $employmentInfo['career_level'];
        }
        if (array_key_exists('career_band', $employmentInfo)) {
            $jobData['career_band'] = $employmentInfo['career_band'];
        }
        if (array_key_exists('career_zone', $employmentInfo)) {
            $jobData['career_zone'] = $employmentInfo['career_zone'];
        }

        if (!empty($jobData)) {
            if ($user->currentJobInformation) {
                $user->currentJobInformation->update($jobData);
            } else {
                $user->jobInformation()->create($jobData);
            }
        }
    }

    private function createAssociateLog(User $user, array $logData): AssociateLog
    {
        $attachmentId = null;
        $attachmentName = null;

        if (isset($logData['attachments']) && count($logData['attachments']) > 0) {
            $file = $logData['attachments'][0];
            
            if ($file instanceof UploadedFile) {
                $attachmentId = time() . '_' . uniqid();
                $attachmentName = $file->getClientOriginalName();
                $file->storeAs('associate-logs', $attachmentId, 'public');
            }
        }

        return $user->associateLogs()->create([
            'entry_details' => $logData['entry_details'],
            'date' => $logData['date'],
            'attachment_id' => $attachmentId,
            'attachment_name' => $attachmentName,
            'created_by' => auth()->id(),
        ]);
    }

    public function canCreateAssociateLog(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'HR', 'Admin']);
    }

    public function canDownloadAttachment(User $user, AssociateLog $log): bool
    {
        return $log->user_id === $user->id || $user->hasRole(['Super Admin', 'HR', 'Admin']);
    }

    public function getAttachmentPath(AssociateLog $log): ?string
    {
        if (!$log->hasAttachment()) {
            return null;
        }

        $path = storage_path('app/public/associate-logs/' . $log->attachment_id);
        
        return file_exists($path) ? $path : null;
    }
}
