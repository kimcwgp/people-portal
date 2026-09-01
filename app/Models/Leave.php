<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'user_id',
        'leaves_type_id',
        'start_date',
        'end_date',
        'duration',
        'time_in',
        'time_out',
        'reason',
        'notes',
        'attachment',
        'status',
        'approver_id',
        'approved_at',
        'rejection_note',
        'cancelled_at',
        'cancelled_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected $appends = ['status_color', 'duration_text', 'attachment_url'];

    protected $sortable = [
        'id',
        'start_date',
        'end_date',
        'status',
        'created_at',
        'updated_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leaves_type_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'approved' => 'green',
            'rejected' => 'red',
            'pending' => 'yellow',
            default => 'gray'
        };
    }

    public function getDurationTextAttribute()
    {
        if ($this->duration === 'Custom' && $this->time_in && $this->time_out) {
            return "Custom ({$this->time_in} - {$this->time_out})";
        }
        
        return $this->duration;
    }

    public function getAttachmentUrlAttribute()
    {
        if (!$this->attachment) return null;
        
        return asset('storage/' . $this->attachment);
    }

    public function getDaysAttribute()
    {
        if (!$this->start_date || !$this->end_date) return 0;
        
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    public function getCalculatedDaysAttribute(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $startDate = $this->start_date instanceof \Carbon\Carbon
            ? $this->start_date
            : \Carbon\Carbon::parse($this->start_date);

        $endDate = $this->end_date instanceof \Carbon\Carbon
            ? $this->end_date
            : \Carbon\Carbon::parse($this->end_date);

        $daysDiff = $startDate->diffInDays($endDate) + 1;

        switch ($this->duration) {
            case 'Half Day (8am to 12nn)':
            case 'Half Day (1pm to 5pm)':
                return $daysDiff == 1 ? 0.5 : $daysDiff;

            case 'Custom':
            case 'All Day':
            default:
                return $daysDiff;
        }
    }
}