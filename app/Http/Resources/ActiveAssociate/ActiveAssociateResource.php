<?php

namespace App\Http\Resources\ActiveAssociate;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ActiveAssociateResource extends JsonResource
{
    public function toArray($request): array
    {
        $attendance = $this->todayAttendance;
        $activeBreak = $attendance?->activeBreak;
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->getAvatarInitials(),
            'status' => $this->getCurrentStatus(),
            'status_color' => $this->getStatusColor(),
            'time_in' => $attendance?->time_in ? 
                Carbon::parse($attendance->time_in)->format('H:i') : null,
            'time_in_formatted' => $attendance?->time_in ? 
                Carbon::parse($attendance->time_in)->format('g:i A') : null,
            'department' => $this->team?->name ?? 'Unassigned',
        ];
    }

    private function getAvatarInitials(): string
    {
        return strtoupper(substr($this->name, 0, 1) . 
               (strpos($this->name, ' ') ? substr($this->name, strpos($this->name, ' ') + 1, 1) : ''));
    }

    private function getCurrentStatus(): string
    {
        $attendance = $this->todayAttendance;
        $activeBreak = $attendance?->activeBreak;
        
        if (!$attendance) {
            return 'Not Clocked In';
        }
        
        if ($activeBreak) {
            return match($activeBreak->type) {
                'lunch' => 'Lunch Break',
                'break' => 'On Break',
                'brb' => 'Be Right Back',
                default => 'On Break'
            };
        }
        
        return 'Active';
    }

    private function getStatusColor(): string
    {
        $attendance = $this->todayAttendance;
        $activeBreak = $attendance?->activeBreak;
        
        if (!$attendance) {
            return 'gray';
        }
        
        if ($activeBreak) {
            return match($activeBreak->type) {
                'lunch' => 'orange',
                'break' => 'yellow',
                'brb' => 'blue',
                default => 'yellow'
            };
        }
        
        return 'green';
    }

    private function getHoursWorked(): string
    {
        $attendance = $this->todayAttendance;
        
        if (!$attendance || !$attendance->time_in) {
            return '0:00';
        }
        
        $timeIn = Carbon::parse($attendance->time_in);
        $now = Carbon::now();
        
        // Calculate total minutes worked
        $totalMinutes = $timeIn->diffInMinutes($now);
        
        // Subtract break time if any
        if ($attendance->breaks) {
            foreach ($attendance->breaks as $break) {
                if ($break->started_at) {
                    $breakStart = Carbon::parse($break->started_at);
                    $breakEnd = $break->ended_at ? Carbon::parse($break->ended_at) : Carbon::now();
                    $totalMinutes -= $breakStart->diffInMinutes($breakEnd);
                }
            }
        }
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        return sprintf('%d:%02d', $hours, $minutes);
    }
}