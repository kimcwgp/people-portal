<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceBreak extends Model
{
    protected $fillable = [
        'attendance_id', 
        'type', 
        'started_at', 
        'ended_at',
        'notes'
    ];

    protected $casts = [
        'started_at' => 'datetime', 
        'ended_at' => 'datetime'
    ];

    // Break type constants
    const TYPE_LUNCH = 'lunch';
    const TYPE_BRB = 'brb';

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->hasOneThrough(User::class, Attendance::class, 'id', 'id', 'attendance_id', 'user_id');
    }

    public function isActive(): bool
    {
        return is_null($this->ended_at);
    }

    public function getDurationInMinutes(): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $endTime = $this->ended_at ?? now();
        return $this->started_at->diffInMinutes($endTime);
    }

    public function scopeLunchBreaks($query)
    {
        return $query->where('type', self::TYPE_LUNCH);
    }

    public function scopeBrbBreaks($query)
    {
        return $query->where('type', self::TYPE_BRB);
    }

    public function isLunchBreak(): bool
    {
        return $this->type === self::TYPE_LUNCH;
    }

    public function isBrbBreak(): bool
    {
        return $this->type === self::TYPE_BRB;
    }
}