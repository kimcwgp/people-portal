<?php

namespace App\Services;

use App\Models\{Attendance, AttendanceBreak};
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BreakCalculationService
{
    /**
     * Get break configuration from environment with proper defaults
     */
    public function getBreakConfig(): array
    {
        $config = [
            'lunch' => [
                'allowance' => (int) env('LUNCH_BREAK_MINUTES', 60), 
                'deduct' => true,  
                'label' => 'Lunch'
            ],
            'brb' => [
                'allowance' => (int) env('BRB_BREAK_MINUTES', 30), 
                'deduct' => true,  
                'label' => 'Be right back'
            ],
        ];
        
        return $config;
    }

    /**
     * Get currently active break for an attendance record
     */
    public function getActiveBreak(Attendance $attendance): ?AttendanceBreak
    {
        return $attendance->breaks()
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }

    /**
     * Calculate minutes used for a specific break type up to a given time
     */
    public function getUsedMinutesForType(Attendance $attendance, string $type, Carbon $until): int
    {
        $config = $this->getBreakConfig();
        if (!isset($config[$type])) {
            return 0;
        }

        $allowance = (int) $config[$type]['allowance'];
        $totalUsed = 0;

        foreach ($attendance->breaks()->where('type', $type)->get() as $break) {
            if (!$break->started_at) {
                continue;
            }
            
            $endTime = $break->ended_at ?: $until;
            $minutes = max(0, $break->started_at->diffInMinutes($endTime));
            $totalUsed += $minutes;
            
            if ($allowance > 0 && $totalUsed >= $allowance) {
                return $allowance;
            }
        }

        return $allowance > 0 ? min($totalUsed, $allowance) : $totalUsed;
    }

    /**
     * Calculate total deductible break minutes across all break types
     */
    public function getTotalDeductibleBreakMinutes(Attendance $attendance, Carbon $until): int
    {
        $total = 0;
        $config = $this->getBreakConfig();

        foreach ($config as $type => $meta) {
            if (!$meta['deduct']) {
                continue;
            }
            $total += $this->getUsedMinutesForType($attendance, $type, $until);
        }

        return $total;
    }

    /**
     * Calculate remaining minutes for a specific break type
     */
    public function getRemainingMinutesForType(Attendance $attendance, string $type, Carbon $now): ?int
    {
        $config = $this->getBreakConfig();
        if (!isset($config[$type])) {
            return null;
        }

        $allowance = (int) $config[$type]['allowance'];
        if ($allowance <= 0) {
            return null;
        }

        $used = $this->getUsedMinutesForType($attendance, $type, $now);
        return max(0, $allowance - $used);
    }

    /**
     * Format minutes into hours and minutes string
     */
    public function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return sprintf('%dh %dm', $hours, $mins);
    }

    /**
     * Calculate net working hours (total worked minus deductible breaks)
     */
    public function calculateNetWorkingMinutes(Attendance $attendance, Carbon $now): int
    {
        if (!$attendance->time_in) {
            return 0;
        }

        $start = $attendance->time_in instanceof Carbon 
            ? $attendance->time_in 
            : Carbon::parse($attendance->time_in);

        $end = $attendance->time_out 
            ? ($attendance->time_out instanceof Carbon ? $attendance->time_out : Carbon::parse($attendance->time_out))
            : $now;

        $totalWorkedMinutes = max(0, $start->diffInMinutes($end));
        $deductibleBreakMinutes = $this->getTotalDeductibleBreakMinutes($attendance, $end);
        
        return max(0, $totalWorkedMinutes - $deductibleBreakMinutes);
    }

    /**
     * Get break allowances as a simple array for frontend (FIXED)
     */
    public function getBreakAllowances(): array
    {
        $config = $this->getBreakConfig();
        $allowances = [];
        
        foreach ($config as $type => $typeConfig) {
            $allowances[$type] = (int) $typeConfig['allowance'];
        }
    
        return $allowances;
    }
}