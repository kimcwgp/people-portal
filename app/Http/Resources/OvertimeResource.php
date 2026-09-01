<?php 

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OvertimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'approver_id' => $this->approver_id,
            'project_manager_id' => $this->project_manager_id,
            'status' => $this->status,
            'ot_date' => $this->ot_date,
            'notes' => $this->notes,
            'rejection_note' => $this->rejection_note,
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_at' => $this->cancelled_at,
            'cancelled_by' => $this->cancelled_by,
            'time_in' => $this->time_in?->format('g:i A'),
            'time_out' => $this->time_out?->format('g:i A'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'formatted_ot_date' => $this->ot_date?->format('M j, Y'),
            'formatted_date' => $this->ot_date?->format('Y-m-d'),
            'relative_date' => $this->created_at?->diffForHumans(),
            'formatted_cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'ot_hours' => $this->ot_hours,
            'formatted_ot_hours' => $this->formatOvertimeHours(),
            'status_color' => $this->status_color,
            'is_pending' => $this->status === 'PENDING',
            'is_approved' => $this->status === 'APPROVED',
            'is_rejected' => $this->status === 'REJECTED',
            'is_cancelled' => $this->status === 'CANCELLED',
            'is_today' => $this->ot_date?->isToday(),
            'can_be_cancelled' => $this->canBeCancelled(),

            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->project_name,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
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

            'project_manager' => $this->whenLoaded('projectManager', function () {
                return [
                    'id' => $this->projectManager->id,
                    'name' => $this->projectManager->name,
                    'email' => $this->projectManager->email,
                ];
            }),
        ];
    }

    private function formatOvertimeHours(): string
    {
        if (empty($this->ot_hours)) {
            return '0 hours';
        }

        $totalHours = $this->ot_hours;
        $hours = floor($totalHours);
        $minutes = round(($totalHours - $hours) * 60);

        if ($hours > 0 && $minutes > 0) {
            $hoursText = $hours === 1 ? 'hour' : 'hours';
            $minutesText = $minutes === 1 ? 'minute' : 'minutes';
            return "{$hours} {$hoursText} {$minutes} {$minutesText}";
        } elseif ($hours > 0) {
            $hoursText = $hours === 1 ? 'hour' : 'hours';
            return "{$hours} {$hoursText}";
        } else {
            $minutesText = $minutes === 1 ? 'minute' : 'minutes';
            return "{$minutes} {$minutesText}";
        }
    }

    private function canBeCancelled(): bool
    {
        return in_array($this->status, ['PENDING', 'APPROVED']);
    }
}