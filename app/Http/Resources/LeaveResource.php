<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LeaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'leaves_type_id' => $this->leaves_type_id,
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'duration' => $this->duration,
            'reason' => $this->reason,
            'status' => $this->status,
            'rejection_note' => $this->rejection_note,
            'time_in' => $this->time_in,
            'time_out' => $this->time_out,
            'notes' => $this->notes,
            'attachment' => $this->attachment,
            'attachment_url' => $this->attachment ? asset('storage/' . $this->attachment) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            'formatted_start_date' => $this->start_date ?
                $this->start_date->format('M j, Y') : null,
            'formatted_end_date' => $this->end_date ?
                $this->end_date->format('M j, Y') : null,
            'relative_date' => $this->created_at ?
                $this->created_at->diffForHumans() : null,

            'calculated_days' => $this->calculated_days,
            'formatted_duration' => $this->formatDuration(),

            'leave_type' => $this->whenLoaded('leaveType', function () {
                return [
                    'id' => $this->leaveType->id,
                    'name' => $this->leaveType->name,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'initials' => $this->getUserInitials($this->user->name),
                    'current_job_information' => $this->user->currentJobInformation ? [
                        'id' => $this->user->currentJobInformation->id,
                        'position_name' => $this->user->currentJobInformation->position_name,
                    ] : null,
                ];
            }),

            'approver' => $this->whenLoaded('approver', function () {
                return [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                    'email' => $this->approver->email,
                ];
            }),

            'attachment_info' => $this->when(
                $this->leave_type_id == 2 && !empty($this->attachment),
                function () {
                    $filePath = 'leave_attachments/' . $this->attachment;
                    $fileExists = Storage::disk('public')->exists($filePath);
                    return [
                        'filename' => $this->attachment,
                        'download_url' => $fileExists ? $this->getDownloadUrl() : null,
                        'file_exists' => $fileExists,
                        'formatted_size' => $fileExists ? $this->formatFileSize(Storage::disk('public')->size($filePath)) : null,
                        'is_image' => $this->isImageFile($this->attachment),
                    ];
                }
            ),

            'has_attachment' => $this->leave_type_id == 2 && !empty($this->attachment),
            'requires_attachment' => $this->leave_type_id == 2,
        ];
    }

    private function getDownloadUrl(): ?string
    {
        if ($this->relationLoaded('user')) {
            return route('my-team-leaves.download-attachment', $this->id);
        }
        return route('my-team-leaves.download-attachment', $this->id);
    }

    private function getUserInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        
        return substr($initials, 0, 2);
    }

    private function formatDuration(): string
    {
        if (empty($this->duration)) {
            return 'Not specified';
        }

        $calculatedDays = $this->calculated_days;

        if ($calculatedDays == 1) {
            return '1 day';
        } elseif ($calculatedDays == 0.5) {
            return '0.5 day';
        } elseif ($calculatedDays < 2) {
            return number_format($calculatedDays, 1) . ' day';
        } else {
            return number_format($calculatedDays, 1) . ' days';
        }
    }

    private function formatFileSize(?int $bytes): string
    {
        if (!$bytes) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function isImageFile(?string $filename): bool
    {
        if (!$filename) return false;

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];

        return in_array($extension, $imageExtensions);
    }
}