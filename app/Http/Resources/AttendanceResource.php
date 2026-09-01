<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AttendanceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'attendance_date' => $this->attendance_date,
            'time_in' => $this->getDisplayTimeIn(),
            'time_out' => $this->getDisplayTimeOut(),
            'original_time_in' => $this->time_in ? Carbon::parse($this->time_in)->format('g:i A') : null,
            'original_time_out' => $this->time_out ? Carbon::parse($this->time_out)->format('g:i A') : null,
            'lunch_start' => $this->getLunchStartTime(),
            'lunch_end' => $this->getLunchEndTime(),
            'original_lunch_start' => $this->getOriginalLunchStartTime(),
            'original_lunch_end' => $this->getOriginalLunchEndTime(),
            'breaks' => $this->getBreakTimes(),
            'notes' => $this->getNotesDisplay(),
            'working_hours' => $this->calculateWorkingHours(),
            'total_break_hours' => $this->calculateTotalBreakHours(),
            'lunch_break_hours' => $this->calculateLunchBreakHours(),
            'status' => $this->getStatus(),
            'is_weekend' => $this->isWeekend(),
            'is_awol' => $this->isAwol(),
            'is_on_leave' => $this->isOnLeave(),
            'is_partial_leave' => $this->isPartialLeave(),
            'leave_info' => $this->getLeaveInfo(),
            'correction' => $this->whenLoaded('correction', function () {
                return [
                    'id' => $this->correction->id,
                    'status' => $this->correction->status,
                    'corrected_time_in' => $this->correction->corrected_time_in,
                    'corrected_time_out' => $this->correction->corrected_time_out,
                    'corrected_lunch_start' => $this->correction->corrected_lunch_start,
                    'corrected_lunch_end' => $this->correction->corrected_lunch_end,
                    'reason' => $this->correction->reason,
                    'rejection_note' => $this->correction->rejection_note,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'initials' => $this->getUserInitials($this->user->name),
                    'team' => $this->whenLoaded('team', function () {
                        return [
                            'id' => $this->user->team->id,
                            'name' => $this->user->team->name,
                        ];
                    }, $this->user->team ? [
                        'id' => $this->user->team->id,
                        'name' => $this->user->team->name,
                    ] : null),
                    'current_job_information' => $this->user->currentJobInformation ? [
                        'id' => $this->user->currentJobInformation->id,
                        'position_name' => $this->user->currentJobInformation->position_name,
                    ] : null,
                ];
            }),
        ];
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

    private function getDisplayTimeIn(): ?string
    {
        if ($this->relationLoaded('correction') && $this->correction && $this->correction->status === 'approved') {
            return $this->correction->corrected_time_in ?? ($this->time_in ? Carbon::parse($this->time_in)->format('g:i A') : null);
        }
        
        return $this->time_in ? Carbon::parse($this->time_in)->format('g:i A') : null;
    }

    private function getDisplayTimeOut(): ?string
    {
        if ($this->relationLoaded('correction') && $this->correction && $this->correction->status === 'approved') {
            return $this->correction->corrected_time_out ?? ($this->time_out ? Carbon::parse($this->time_out)->format('g:i A') : null);
        }
        
        return $this->time_out ? Carbon::parse($this->time_out)->format('g:i A') : null;
    }

    private function getNotesDisplay(): string
    {
        if ($this->isOnLeave()) {
            return $this->formatLeaveNote();
        }

        if ($this->isWeekend()) {
            return 'Weekend';
        }

        if ($this->isAwol()) {
            return 'AWOL';
        }

        return $this->notes ?? 'No notes';
    }

    private function isOnLeave(): bool
    {
        return !empty($this->leaves_for_date) && $this->leaves_for_date->isNotEmpty();
    }

    private function isPartialLeave(): bool
    {
        if (!$this->isOnLeave()) {
            return false;
        }

        $leave = $this->leaves_for_date->first();
        if (!$leave) {
            return false;
        }

        $duration = strtolower($leave->duration ?? '');
        
        return !in_array($duration, ['all day', 'allday', 'full day', 'fullday', '']);
    }

    private function getLeaveInfo(): ?array
    {
        if (!$this->isOnLeave()) {
            return null;
        }

        $leave = $this->leaves_for_date->first();
        if (!$leave) {
            return null;
        }
        
        return [
            'id' => $leave->id,
            'leave_type' => $leave->leaveType->name ?? 'Leave',
            'reason' => $leave->reason,
            'duration' => $leave->duration,
            'status' => $leave->status,
            'start_date' => $leave->start_date,
            'end_date' => $leave->end_date,
            'is_partial' => $this->isPartialLeave(),
            'has_attachment' => !empty($leave->attachment),
            'attachment' => $leave->attachment,
            'attachment_url' => $leave->attachment ? asset('storage/' . $leave->attachment) : null,
        ];
    }

    private function formatLeaveNote(): string
    {
        if (!$this->isOnLeave()) {
            return '';
        }

        $leave = $this->leaves_for_date->first();
        if (!$leave) {
            return '';
        }

        $leaveType = $leave->leaveType->name ?? 'Leave';
        $status = strtoupper($leave->status);
        $duration = $leave->duration;
        
        if ($this->isPartialLeave()) {
            $leaveNote = "PARTIAL LEAVE: {$leaveType}";
            
            if ($duration) {
                $leaveNote .= " ({$duration})";
            }
        } else {
            $leaveNote = "ON LEAVE: {$leaveType}";
        }
        
        $leaveNote .= " [{$status}]";
        
        if ($leave->reason) {
            $leaveNote .= " - {$leave->reason}";
        }
        
        return $leaveNote;
    }

    private function isWeekend(): bool
    {
        $date = Carbon::parse($this->attendance_date);
        return $date->isWeekend();
    }

    private function isAwol(): bool
    {
        if ($this->isWeekend()) {
            return false;
        }

        if ($this->isOnLeave() && !$this->isPartialLeave()) {
            return false;
        }

        if ($this->isPartialLeave()) {
            $leave = $this->leaves_for_date->first();
            if (!$leave) {
                return false;
            }

            $duration = strtolower($leave->duration ?? '');
            $shift = $this->shift;
            
            if ($duration === 'custom' && $leave->time_in && $leave->time_out && $shift) {
                $leaveStart = Carbon::parse($leave->time_in);
                $leaveEnd = Carbon::parse($leave->time_out);
                $shiftStart = Carbon::parse($shift->start_time);
                
                if ($shift->shift_type === 'night' && $shiftStart->hour >= 18) {
                    $shiftStart = $shiftStart->addDay();
                }
                
                if ($shiftStart->between($leaveStart, $leaveEnd)) {
                    return false;
                }
            }
            
            if ($shift && $shift->shift_type === 'day') {
                if (in_array($duration, ['morning', 'am', 'first half']) && !$this->time_in) {
                    return false;
                }
                
                if (str_contains($duration, '8am to 12nn') && !$this->time_in) {
                    return false;
                }
            }
        }

        if ($this->time_in) {
            return false;
        }

        $attendanceDate = Carbon::parse($this->attendance_date);
        $now = Carbon::now();
        
        if ($attendanceDate->isFuture()) {
            return false;
        }
        
        if ($attendanceDate->isToday()) {
            $shift = $this->shift;
            
            if ($shift) {
                $shiftStartTime = Carbon::parse($shift->start_time);
                $cutoffTime = $now->copy()->setTime($shiftStartTime->hour, $shiftStartTime->minute, 0)->addHours(2);
                return $now->gte($cutoffTime);
            }
            
            $fivePM = $now->copy()->setTime(17, 0, 0);
            return $now->gte($fivePM);
        }
        
        return $attendanceDate->isPast();
    }

    private function calculateWorkingHours(): string
    {
        if ($this->isOnLeave() && !$this->isPartialLeave()) {
            return '--:--';
        }

        $timeInDisplay = $this->getDisplayTimeIn();
        $timeOutDisplay = $this->getDisplayTimeOut();

        if (!$timeInDisplay || !$timeOutDisplay) {
            return '--:--';
        }

        try {
            $baseDate = $this->attendance_date ? Carbon::parse($this->attendance_date) : Carbon::today();
            $baseDateStr = $baseDate->format('Y-m-d');
            
            $timeIn = Carbon::parse($baseDateStr . ' ' . $timeInDisplay);
            $timeOut = Carbon::parse($baseDateStr . ' ' . $timeOutDisplay);

            if ($timeOut->lt($timeIn)) {
                $timeOut->addDay();
            }

            $diffInMinutes = intval($timeIn->diffInMinutes($timeOut));
            $totalBreakMinutes = $this->calculateTotalBreakMinutes();
            $lunchBreakMinutes = $this->calculateLunchBreakMinutes();

            $netWorkingMinutes = max(0, $diffInMinutes - $totalBreakMinutes - $lunchBreakMinutes);

            if ($this->isPartialLeave()) {
                $leaveMinutes = $this->calculateLeaveMinutes();
                $netWorkingMinutes = max(0, $netWorkingMinutes - $leaveMinutes);
            }
            
            $hours = intval($netWorkingMinutes / 60);
            $minutes = $netWorkingMinutes % 60;
            
            return sprintf('%dh %dm', $hours, $minutes);
        } catch (\Exception $e) {
            return '--:--';
        }
    }

    private function calculateLeaveMinutes(): int
    {
        if (!$this->isPartialLeave()) {
            return 0;
        }

        $leave = $this->leaves_for_date->first();
        if (!$leave) {
            return 0;
        }

        $duration = strtolower($leave->duration ?? '');
        
        switch ($duration) {
            case 'half day':
            case 'halfday':
            case 'morning':
            case 'afternoon':
            case 'am':
            case 'pm':
            case 'first half':
            case 'second half':
                return 240;
            
            default:
                if (preg_match('/(\d+)\s*h/', $duration, $matches)) {
                    return intval($matches[1]) * 60;
                }
                if (preg_match('/(\d+)\s*hour/', $duration, $matches)) {
                    return intval($matches[1]) * 60;
                }
                
                return 240;
        }
    }

    private function calculateLunchBreakHours(): string
    {
        try {
            $lunchBreakMinutes = $this->calculateLunchBreakMinutes();
            
            if ($lunchBreakMinutes === 0) {
                return '--:--';
            }
            
            $hours = intval(floor($lunchBreakMinutes / 60));
            $minutes = intval($lunchBreakMinutes % 60);
            
            return sprintf('%dh %dm', $hours, $minutes);
        } catch (\Exception $e) {
            return '--:--';
        }
    }

    private function calculateTotalBreakHours(): string
    {
        try {
            $totalBreakMinutes = $this->calculateTotalBreakMinutes();
            
            if ($totalBreakMinutes === 0) {
                return '--:--';
            }
            
            $hours = intval(floor($totalBreakMinutes / 60));
            $minutes = intval($totalBreakMinutes % 60);
            
            return sprintf('%dh %dm', $hours, $minutes);
        } catch (\Exception $e) {
            return '--:--';
        }
    }

    private function calculateTotalBreakMinutes(): int
    {
        if (!$this->relationLoaded('breaks') || !$this->breaks) {
            return 0;
        }

        $totalMinutes = 0;

        foreach ($this->breaks as $break) {
            if (!$break->started_at) {
                continue;
            }

            try {
                $startTime = Carbon::parse($break->started_at);
                
                $endTime = $break->ended_at
                    ? Carbon::parse($break->ended_at)
                    : ($this->time_out ? Carbon::parse($this->time_out) : Carbon::now());

                $breakMinutes = $startTime->diffInMinutes($endTime);

                if ($break->type !== 'lunch') {
                    $totalMinutes += $breakMinutes;
                }
                
            } catch (\Exception $e) {
                continue;
            }
        }

        return $totalMinutes;
    }

    private function calculateLunchBreakMinutes(): int
    {
        if ($this->relationLoaded('correction') && $this->correction && $this->correction->status === 'approved') {
            if ($this->correction->corrected_lunch_start && $this->correction->corrected_lunch_end) {
                try {
                    $baseDate = $this->attendance_date ? Carbon::parse($this->attendance_date) : Carbon::today();
                    $baseDateStr = $baseDate->format('Y-m-d');
                    
                    $lunchStart = Carbon::parse($baseDateStr . ' ' . $this->correction->corrected_lunch_start);
                    $lunchEnd = Carbon::parse($baseDateStr . ' ' . $this->correction->corrected_lunch_end);
                    
                    return intval($lunchStart->diffInMinutes($lunchEnd));
                } catch (\Exception $e) {
                }
            }
        }

        if (!$this->relationLoaded('breaks') || !$this->breaks) {
            return 0;
        }

        $lunchMinutes = 0;

        foreach ($this->breaks as $break) {
            if (!$break->started_at || $break->type !== 'lunch') {
                continue;
            }

            try {
                $startTime = Carbon::parse($break->started_at);

                $endTime = $break->ended_at
                    ? Carbon::parse($break->ended_at)
                    : ($this->time_out ? Carbon::parse($this->time_out) : Carbon::now());

                $lunchMinutes += $startTime->diffInMinutes($endTime);
            } catch (\Exception $e) {
                continue;
            }
        }

        return $lunchMinutes;
    }

    private function getStatus(): string
    {
        if ($this->isOnLeave() && !$this->isPartialLeave()) {
            return 'on_leave';
        }

        if ($this->isPartialLeave()) {
            return 'partial_leave';
        }

        if ($this->isAwol()) {
            return 'awol';
        }

        if ($this->isWeekend()) {
            return 'weekend';
        }

        $timeInDisplay = $this->getDisplayTimeIn();
        $timeOutDisplay = $this->getDisplayTimeOut();

        if (!$timeInDisplay) return 'not_clocked_in';
        if (!$timeOutDisplay) return 'active';

        try {
            $baseDate = $this->attendance_date ? Carbon::parse($this->attendance_date) : Carbon::today();
            $baseDateStr = $baseDate->format('Y-m-d');
            
            $timeIn = Carbon::parse($baseDateStr . ' ' . $timeInDisplay);
            $timeOut = Carbon::parse($baseDateStr . ' ' . $timeOutDisplay);

            if ($timeOut->lt($timeIn)) {
                $timeOut->addDay();
            }

            $totalMinutes = intval(abs($timeIn->diffInMinutes($timeOut)));
            $breakMinutes = $this->calculateTotalBreakMinutes();
            $lunchBreakMinutes = $this->calculateLunchBreakMinutes();

            $netWorkingMinutes = max(0, $totalMinutes - $breakMinutes - $lunchBreakMinutes);
            $netHours = $netWorkingMinutes / 60;

            if ($netHours >= 7.5) return 'complete';
            return 'undertime';
        } catch (\Exception $e) {
            return 'invalid';
        }
}
    private function getLunchStartTime(): ?string
    {
        if ($this->relationLoaded('correction') && $this->correction && $this->correction->status === 'approved') {
            if ($this->correction->corrected_lunch_start) {
                return $this->correction->corrected_lunch_start;
            }
        }

        return $this->getOriginalLunchStartTime();
    }

    private function getLunchEndTime(): ?string
    {
        if ($this->relationLoaded('correction') && $this->correction && $this->correction->status === 'approved') {
            if ($this->correction->corrected_lunch_end) {
                return $this->correction->corrected_lunch_end;
            }
        }

        return $this->getOriginalLunchEndTime();
    }

    private function getOriginalLunchStartTime(): ?string
    {
        if (!$this->relationLoaded('breaks') || !$this->breaks) {
            return null;
        }

        $lunchBreak = $this->breaks->firstWhere('type', 'lunch');
        
        if (!$lunchBreak || !$lunchBreak->started_at) {
            return null;
        }

        return Carbon::parse($lunchBreak->started_at)->format('g:i A');
    }

    private function getOriginalLunchEndTime(): ?string
    {
        if (!$this->relationLoaded('breaks') || !$this->breaks) {
            return null;
        }

        $lunchBreak = $this->breaks->firstWhere('type', 'lunch');
        
        if (!$lunchBreak || !$lunchBreak->ended_at) {
            return null;
        }

        return Carbon::parse($lunchBreak->ended_at)->format('g:i A');
    }

    private function getBreakTimes(): array
    {
        if (!$this->relationLoaded('breaks') || !$this->breaks) {
            return [];
        }

        $breakTimes = [];
        
        foreach ($this->breaks as $break) {
            if ($break->type === 'lunch') {
                continue;
            }
            
            if (!$break->started_at) {
                continue;
            }

            $breakTimes[] = [
                'start' => Carbon::parse($break->started_at)->format('g:i A'),
                'end' => $break->ended_at 
                    ? Carbon::parse($break->ended_at)->format('g:i A')
                    : null,
            ];
        }

        return $breakTimes;
    }
}
