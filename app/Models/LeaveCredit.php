<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCredit extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'vl_credits',
        'vl_used',
        'vl_pending',
        'vl_carried_over',
        'vl_carried_over_used',
        'sl_credits',
        'sl_used',
        'sl_pending',
        'pto_credits',
        'pto_used',
        'pto_pending',
        'cto_hours',
        'cto_used_hours',
        'cto_pending_hours',
        'birthday_leave_count',
    ];

    protected $casts = [
        'vl_credits' => 'decimal:2',
        'vl_used' => 'decimal:2',
        'vl_pending' => 'decimal:2',
        'vl_carried_over' => 'decimal:2',
        'vl_carried_over_used' => 'decimal:2',
        'sl_credits' => 'decimal:2',
        'sl_used' => 'decimal:2',
        'sl_pending' => 'decimal:2',
        'pto_credits' => 'decimal:2',
        'pto_used' => 'decimal:2',
        'pto_pending' => 'decimal:2',
        'cto_hours' => 'decimal:2',
        'cto_used_hours' => 'decimal:2',
        'cto_pending_hours' => 'decimal:2',
        'birthday_leave_count' => 'decimal:2',
    ];

    protected $appends = [
        'vl_remaining', 
        'sl_remaining', 
        'total_vl', 
        'total_sl',
        'vl_carried_over_remaining',
        'vl_current_year_remaining',
        'pto_remaining',
        'cto_remaining_hours'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getVlRemainingAttribute(): float
    {
        $remainingCarryover = ($this->vl_carried_over ?? 0) - ($this->vl_carried_over_used ?? 0);
        $pendingFromCarryover = min($this->vl_pending, $remainingCarryover);
        $pendingFromCurrentYear = max(0, $this->vl_pending - $pendingFromCarryover);
        
        return $this->vl_credits - $this->vl_used - $pendingFromCurrentYear;
    }

    public function getSlRemainingAttribute(): float
    {
        return $this->sl_credits - $this->sl_used - $this->sl_pending;
    }

    /** PTO is tracked in days, same shape as VL and SL. */
    public function getPtoRemainingAttribute(): float
    {
        return (float) $this->pto_credits - (float) $this->pto_used - (float) $this->pto_pending;
    }

    /** CTO is compensatory time off granted by HR, tracked in HOURS, not days. */
    public function getCtoRemainingHoursAttribute(): float
    {
        return (float) $this->cto_hours - (float) $this->cto_used_hours - (float) $this->cto_pending_hours;
    }

    public function getTotalVlAttribute(): float
    {
        return $this->vl_credits + $this->vl_carried_over;
    }

    public function getTotalSlAttribute(): float
    {
        return $this->sl_credits;
    }

    public function getVlCarriedOverRemainingAttribute(): float
    {
        $carriedOver = $this->vl_carried_over ?? 0;
        $carriedOverUsed = $this->vl_carried_over_used ?? 0;
        $pending = $this->vl_pending ?? 0;
        
        $availableCarryover = $carriedOver - $carriedOverUsed;
        $pendingFromCarryover = min($pending, $availableCarryover);
        
        return max(0, $availableCarryover - $pendingFromCarryover);
    }

    public function getVlCurrentYearRemainingAttribute(): float
    {
        $currentYearCredits = $this->vl_credits ?? 0;
        $totalUsed = $this->vl_used ?? 0;
        $carriedOverUsed = $this->vl_carried_over_used ?? 0;
        
        $currentYearUsed = max(0, $totalUsed - $carriedOverUsed);
        
        return max(0, $currentYearCredits - $currentYearUsed);
    }

    /**
     * Check if user is eligible for birthday leave
     * Must be at least 1 year from hire date
     */
    public function isEligibleForBirthdayLeave(): bool
    {
        if (!$this->user || !$this->user->employee) {
            return false;
        }

        $hireDate = $this->user->employee->hire_date;
        if (!$hireDate) {
            return false;
        }

        // Must be at least 1 year from hire date
        $oneYearFromHire = \Carbon\Carbon::parse($hireDate)->addYear();
        
        return now()->greaterThanOrEqualTo($oneYearFromHire);
    }

    /**
     * Get birthday leave count based on eligibility
     */
    public function getBirthdayLeaveAvailableAttribute(): float
    {
        return $this->isEligibleForBirthdayLeave() ? ($this->birthday_leave_count ?? 0) : 0;
    }
}
