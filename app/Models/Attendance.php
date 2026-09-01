<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'user_id',
        'shift_id',
        'notes',
        'healthcheckJson',
        'time_in',
        'time_out',
        'attendance_date',
        'lunch_break',
        'be_right_back'
    ];

        protected $casts = [
        'healthcheckJson' => 'array',
        'attendance_date' => 'date',
        'lunch_break' => 'datetime',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
    ];

    protected $sortable = [
        'id',
        'attendance_date',
        'time_in',
        'time_out',
        'lunch_break',
        'be_right_back',
        'created_at',
        'updated_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function activeBreak(): HasOne
    {
        return $this->hasOne(AttendanceBreak::class)
                    ->whereNull('ended_at')
                    ->latest();
    }

    public function correction(): HasOne
    {
        return $this->hasOne(AttendanceCorrection::class)->latest();
    }

    public function isOnBreak(): bool
    {
        return $this->activeBreak()->exists();
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('time_in')
                    ->whereNull('time_out');
    }

    public function scopeComplete($query)
    {
        return $query->whereNotNull('time_in')
                    ->whereNotNull('time_out');
    }

    public function scopeOnBreak($query)
    {
        return $query->whereHas('activeBreak');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('attendance_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('attendance_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('attendance_date', now()->month)
                    ->whereYear('attendance_date', now()->year);
    }

    public function getWorkingHoursAttribute(): ?string
    {
        if (!$this->time_in || !$this->time_out) {
            return null;
        }

        $timeIn = $this->time_in;
        $timeOut = $this->time_out;

        $totalMinutes = $timeOut->diffInMinutes($timeIn);

        $breakMinutes = $this->breaks->sum(function ($break) {
            return $break->ended_at ?
                $break->ended_at->diffInMinutes($break->started_at) : 0;
        });

        $workingMinutes = $totalMinutes - $breakMinutes;

        $hours = intval($workingMinutes / 60);
        $minutes = $workingMinutes % 60;

        return sprintf('%dh %dm', $hours, $minutes);
    }

    public function getTotalBreakHoursAttribute(): string
    {
        $totalBreakMinutes = $this->breaks->sum(function ($break) {
            return $break->ended_at ?
                $break->ended_at->diffInMinutes($break->started_at) : 0;
        });

        if ($totalBreakMinutes === 0) {
            return '--:--';
        }

        $hours = intval($totalBreakMinutes / 60);
        $minutes = $totalBreakMinutes % 60;

        return sprintf('%dh %dm', $hours, $minutes);
    }

    public function getIsWeekendAttribute(): bool
    {
        return $this->attendance_date->isWeekend();
    }

    public function getStatusAttribute(): string
    {
        if (!$this->time_in) {
            return 'absent';
        }

        if (!$this->time_out) {
            return 'active';
        }

        return 'complete';
    }
}