<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Traits\Filterable;
use App\Traits\Sortable;

class Overtime extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'project_id',
        'user_id',
        'project_manager_id',
        'approver_id',
        'status',
        'ot_date',
        'notes',
        'time_in',
        'time_out',
        'rejection_note',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by',
    ];

    protected $casts = [
        'ot_date' => 'date',
        'time_in' => 'datetime:H:i',
        'time_out' => 'datetime:H:i',
        'cancelled_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'PENDING',
    ];

    protected $sortable = [
        'id',
        'ot_date',
        'created_at',
        'updated_at',
        'status',
        'user_id',
        'project_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopeForUser($query, $userId = null)
    {
        return $query->where('user_id', $userId ?? auth()->id());
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('ot_date', [$startDate, $endDate]);
    }

    public function getOtHoursAttribute(): float
    {
        if (!$this->time_in || !$this->time_out) {
            return 0;
        }

        try {
            $timeInStr = $this->time_in->format('H:i');
            $timeOutStr = $this->time_out->format('H:i');

            $timeIn = Carbon::createFromFormat('H:i', $timeInStr);
            $timeOut = Carbon::createFromFormat('H:i', $timeOutStr);

            if ($timeOut->lt($timeIn)) {
                $timeOut->addDay();
            }

            return round($timeOut->diffInHours($timeIn, true), 2);
        } catch (\Exception $e) {
            \Log::error('Error calculating overtime hours: ' . $e->getMessage());
            return 0;
        }
    }

    public function getFormattedOtHoursAttribute(): string
    {
        $hours = $this->ot_hours;

        if ($hours == 0) {
            return '0h';
        }

        $wholeHours = floor($hours);
        $minutes = ($hours - $wholeHours) * 60;

        if ($minutes > 0) {
            return $wholeHours . 'h ' . round($minutes) . 'm';
        }

        return $wholeHours . 'h';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'PENDING' => 'yellow',
            'APPROVED' => 'green',
            'REJECTED' => 'red',
            'CANCELLED' => 'gray',
            default => 'gray'
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['PENDING', 'APPROVED']);
    }
}