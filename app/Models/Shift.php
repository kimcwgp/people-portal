<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Sortable;
use App\Traits\Filterable;

class Shift extends Model
{
    use HasFactory, Sortable, Filterable;

    protected $fillable = [
        'shift_type',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    protected $sortable = [
        'id',
        'shift_type',
        'start_time',
        'end_time',
        'created_at',
        'updated_at',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getShiftLabelAttribute(): string
    {
        return ucfirst($this->shift_type) . ' (' . 
               $this->start_time->format('g:i A') . ' - ' . 
               $this->end_time->format('g:i A') . ')';
    }

    public function scopeFilterByShiftType($query, $shiftType = null)
    {
        if (!$shiftType) return $query;

        return $query->where('shift_type', $shiftType);
    }

    public function scopeDayShifts($query)
    {
        return $query->where('shift_type', 'day');
    }

    public function scopeNightShifts($query)
    {
        return $query->where('shift_type', 'night');
    }

    public function getUsersCountAttribute(): int
    {
        // Check if users_count was already loaded via withCount/loadCount
        if (array_key_exists('users_count', $this->attributes)) {
            return (int) $this->attributes['users_count'];
        }

        // Fallback - avoid using this in loops
        return $this->users()->count();
    }

    public function isDayShift(): bool
    {
        return $this->shift_type === 'day';
    }

    public function isNightShift(): bool
    {
        return $this->shift_type === 'night';
    }

    public function getDurationInHours(): float
    {
        $start = $this->start_time;
        $end = $this->end_time;

        if ($end < $start) {
            $end = $end->addDay();
        }

        return $start->diffInHours($end);
    }
}