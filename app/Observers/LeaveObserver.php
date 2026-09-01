<?php

namespace App\Observers;

use App\Models\{Leave, LeaveCredit};
use Illuminate\Support\Facades\Log;

class LeaveObserver
{
    public function updated(Leave $leave): void
    {
        if (!$leave->wasChanged('status')) {
            return;
        }

        $originalStatus = $leave->getOriginal('status');
        $newStatus = $leave->status;

        $leave->load('leaveType');
        
        if (!$leave->leaveType) {
            Log::warning('Leave type not found for leave ID: ' . $leave->id);
            return;
        }

        $leaveTypeName = strtolower($leave->leaveType->name);
        
        if (str_contains($leaveTypeName, 'birthday')) {
            $this->handleBirthdayLeave($leave, $originalStatus, $newStatus);
            return;
        }
        
        if (str_contains($leaveTypeName, 'w/o pay') || 
            str_contains($leaveTypeName, 'without pay')) {
            return;
        }
        
        $isVL = str_contains($leaveTypeName, 'vacation') || 
                str_contains($leaveTypeName, 'emergency');
        
        $isSL = str_contains($leaveTypeName, 'sick');

        if (!$isVL && !$isSL) {
            return;
        }

        $year = $leave->start_date->year;
        $days = $leave->calculated_days;

        // Check if user is eligible for birthday leave (1 year from hire date)
        $user = $leave->user;
        $birthdayLeave = 0;
        
        if ($user && $user->employee && $user->employee->hire_date) {
            $oneYearFromHire = \Carbon\Carbon::parse($user->employee->hire_date)->addYear();
            if (now()->greaterThanOrEqualTo($oneYearFromHire)) {
                $birthdayLeave = 1.00;
            }
        }

        $leaveCredit = LeaveCredit::firstOrCreate(
            [
                'user_id' => $leave->user_id,
                'year' => $year
            ],
            [
                'vl_credits' => 0,
                'sl_credits' => 0,
                'vl_used' => 0,
                'sl_used' => 0,
                'vl_pending' => 0,
                'sl_pending' => 0,
                'vl_carried_over' => 0,
                'birthday_leave_count' => $birthdayLeave,
            ]
        );

        if ($newStatus === 'approved' && $originalStatus === 'pending') {
            if ($isVL) {
                $leaveCredit->vl_pending = max(0, $leaveCredit->vl_pending - $days);
                
                $remainingCarryover = ($leaveCredit->vl_carried_over ?? 0) - ($leaveCredit->vl_carried_over_used ?? 0);
                
                if ($remainingCarryover > 0) {
                    $deductFromCarryover = min($days, $remainingCarryover);
                    $leaveCredit->vl_carried_over_used = ($leaveCredit->vl_carried_over_used ?? 0) + $deductFromCarryover;
                    
                    $remainingDays = $days - $deductFromCarryover;
                    if ($remainingDays > 0) {
                        $leaveCredit->vl_used += $remainingDays;
                    }
                } else {
                    $leaveCredit->vl_used += $days;
                }
            } else {
                $leaveCredit->sl_pending = max(0, $leaveCredit->sl_pending - $days);
                $leaveCredit->sl_used += $days;
            }
        } elseif ($newStatus === 'rejected' && $originalStatus === 'pending') {
            // Rejected: Remove from pending
            if ($isVL) {
                $leaveCredit->vl_pending = max(0, $leaveCredit->vl_pending - $days);
            } else {
                $leaveCredit->sl_pending = max(0, $leaveCredit->sl_pending - $days);
            }
        } elseif ($newStatus === 'cancelled' && $originalStatus === 'approved') {
            // Cancelled after approval: Remove from used (reverse the deduction logic)
            if ($isVL) {
                // First, try to reverse carryover usage
                $carriedOverUsed = $leaveCredit->vl_carried_over_used ?? 0;
                
                if ($carriedOverUsed > 0) {
                    $returnToCarryover = min($days, $carriedOverUsed);
                    $leaveCredit->vl_carried_over_used = max(0, $carriedOverUsed - $returnToCarryover);
                    
                    $remainingDays = $days - $returnToCarryover;
                    if ($remainingDays > 0) {
                        $leaveCredit->vl_used = max(0, $leaveCredit->vl_used - $remainingDays);
                    }
                } else {
                    $leaveCredit->vl_used = max(0, $leaveCredit->vl_used - $days);
                }
            } else {
                $leaveCredit->sl_used = max(0, $leaveCredit->sl_used - $days);
            }
        } elseif ($newStatus === 'cancelled' && $originalStatus === 'pending') {
            if ($isVL) {
                $leaveCredit->vl_pending = max(0, $leaveCredit->vl_pending - $days);
            } else {
                $leaveCredit->sl_pending = max(0, $leaveCredit->sl_pending - $days);
            }
        }

        $leaveCredit->save();

        Log::info('Leave credits updated', [
            'leave_id' => $leave->id,
            'user_id' => $leave->user_id,
            'type' => $isVL ? 'VL' : 'SL',
            'days' => $days,
            'status_change' => "$originalStatus → $newStatus",
            'vl_used' => $leaveCredit->vl_used,
            'sl_used' => $leaveCredit->sl_used,
        ]);
    }

    public function created(Leave $leave): void
    {
        if ($leave->status !== 'pending') {
            return;
        }

        $leave->load('leaveType');
        
        if (!$leave->leaveType) {
            return;
        }

        $leaveTypeName = strtolower($leave->leaveType->name);
        
        if (str_contains($leaveTypeName, 'birthday') || 
            str_contains($leaveTypeName, 'w/o pay') || 
            str_contains($leaveTypeName, 'without pay')) {
            return;
        }
        
        $isVL = str_contains($leaveTypeName, 'vacation') || 
                str_contains($leaveTypeName, 'emergency');
        
        $isSL = str_contains($leaveTypeName, 'sick');

        if (!$isVL && !$isSL) {
            return;
        }

        $year = $leave->start_date->year;
        $days = $leave->calculated_days;

        // Check if user is eligible for birthday leave (1 year from hire date)
        $user = $leave->user;
        $birthdayLeave = 0;
        
        if ($user && $user->employee && $user->employee->hire_date) {
            $oneYearFromHire = \Carbon\Carbon::parse($user->employee->hire_date)->addYear();
            if (now()->greaterThanOrEqualTo($oneYearFromHire)) {
                $birthdayLeave = 1.00;
            }
        }
        
        $leaveCredit = LeaveCredit::firstOrCreate(
            [
                'user_id' => $leave->user_id,
                'year' => $year
            ],
            [
                'vl_credits' => 0,
                'sl_credits' => 0,
                'vl_used' => 0,
                'sl_used' => 0,
                'vl_pending' => 0,
                'sl_pending' => 0,
                'vl_carried_over' => 0,
                'birthday_leave_count' => $birthdayLeave,
            ]
        );

        if ($isVL) {
            $leaveCredit->vl_pending += $days;
        } else {
            $leaveCredit->sl_pending += $days;
        }

        $leaveCredit->save();

        Log::info('Leave pending added to credits', [
            'leave_id' => $leave->id,
            'user_id' => $leave->user_id,
            'type' => $isVL ? 'VL' : 'SL',
            'days' => $days,
        ]);
    }

    public function deleted(Leave $leave): void
    {
        $leave->load('leaveType');
        
        if (!$leave->leaveType) {
            return;
        }

        $leaveTypeName = strtolower($leave->leaveType->name);
        
        if (str_contains($leaveTypeName, 'birthday') || 
            str_contains($leaveTypeName, 'w/o pay') || 
            str_contains($leaveTypeName, 'without pay')) {
            return;
        }
        
        $isVL = str_contains($leaveTypeName, 'vacation') || 
                str_contains($leaveTypeName, 'emergency');
        
        $isSL = str_contains($leaveTypeName, 'sick');

        if (!$isVL && !$isSL) {
            return;
        }

        $year = $leave->start_date->year;
        $days = $leave->calculated_days;

        $leaveCredit = LeaveCredit::where('user_id', $leave->user_id)
            ->where('year', $year)
            ->first();

        if (!$leaveCredit) {
            return;
        }

        if ($leave->status === 'approved') {
            if ($isVL) {
                $carriedOverUsed = $leaveCredit->vl_carried_over_used ?? 0;
                
                if ($carriedOverUsed > 0) {
                    $returnToCarryover = min($days, $carriedOverUsed);
                    $leaveCredit->vl_carried_over_used = max(0, $carriedOverUsed - $returnToCarryover);
                    
                    $remainingDays = $days - $returnToCarryover;
                    if ($remainingDays > 0) {
                        $leaveCredit->vl_used = max(0, $leaveCredit->vl_used - $remainingDays);
                    }
                } else {
                    $leaveCredit->vl_used = max(0, $leaveCredit->vl_used - $days);
                }
            } else {
                $leaveCredit->sl_used = max(0, $leaveCredit->sl_used - $days);
            }
        } elseif ($leave->status === 'pending') {
            if ($isVL) {
                $leaveCredit->vl_pending = max(0, $leaveCredit->vl_pending - $days);
            } else {
                $leaveCredit->sl_pending = max(0, $leaveCredit->sl_pending - $days);
            }
        }

        $leaveCredit->save();

        Log::info('Leave deleted, credits updated', [
            'leave_id' => $leave->id,
            'user_id' => $leave->user_id,
            'type' => $isVL ? 'VL' : 'SL',
            'days' => $days,
        ]);
    }

    public function restored(Leave $leave): void
    {
        $this->created($leave);
    }

    public function forceDeleted(Leave $leave): void
    {
        $this->deleted($leave);
    }

    private function handleBirthdayLeave(Leave $leave, string $originalStatus, string $newStatus): void
    {
        $year = $leave->start_date->year;
        $days = $leave->calculated_days;

        // Check if user is eligible for birthday leave (1 year from hire date)
        $user = $leave->user;
        $birthdayLeave = 0;
        
        if ($user && $user->employee && $user->employee->hire_date) {
            $oneYearFromHire = \Carbon\Carbon::parse($user->employee->hire_date)->addYear();
            if (now()->greaterThanOrEqualTo($oneYearFromHire)) {
                $birthdayLeave = 1.00;
            }
        }

        $leaveCredit = LeaveCredit::firstOrCreate(
            [
                'user_id' => $leave->user_id,
                'year' => $year
            ],
            [
                'vl_credits' => 0,
                'sl_credits' => 0,
                'vl_used' => 0,
                'sl_used' => 0,
                'vl_pending' => 0,
                'sl_pending' => 0,
                'vl_carried_over' => 0,
                'birthday_leave_count' => $birthdayLeave,
            ]
        );

        if ($newStatus === 'approved' && $originalStatus === 'pending') {
            $leaveCredit->birthday_leave_count = max(0, ($leaveCredit->birthday_leave_count ?? $birthdayLeave) - $days);
        } elseif (($newStatus === 'cancelled' || $newStatus === 'rejected') && $originalStatus === 'approved') {
            $leaveCredit->birthday_leave_count = min($birthdayLeave, ($leaveCredit->birthday_leave_count ?? 0) + $days);
        }

        $leaveCredit->save();

        Log::info('Birthday leave updated', [
            'leave_id' => $leave->id,
            'user_id' => $leave->user_id,
            'days' => $days,
            'status_change' => "$originalStatus → $newStatus",
            'birthday_leave_count' => $leaveCredit->birthday_leave_count,
        ]);
    }
}
