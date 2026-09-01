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
        'birthday_leave_count' => 'decimal:2',
    ];

    protected $appends = [
        'vl_remaining', 
        'sl_remaining', 
        'total_vl', 
        'total_sl',
        'vl_carried_over_remaining',
        'vl_current_year_remaining'
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
