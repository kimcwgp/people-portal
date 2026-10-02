<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimesheetEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'timesheet_id', 'project_id', 'project_ticket', 'time_type_id', 'memo',
        'mon_hours', 'tue_hours', 'wed_hours', 'thu_hours', 'fri_hours', 'sat_hours', 'sun_hours',
        'sort_order',
    ];

    protected $casts = [
        'mon_hours' => 'decimal:2', 'tue_hours' => 'decimal:2', 'wed_hours' => 'decimal:2',
        'thu_hours' => 'decimal:2', 'fri_hours' => 'decimal:2', 'sat_hours' => 'decimal:2',
        'sun_hours' => 'decimal:2', 'sort_order' => 'integer',
    ];

    protected $appends = ['row_total'];

    public function timesheet(): BelongsTo
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function timeType(): BelongsTo
    {
        return $this->belongsTo(TimeType::class);
    }

    public function getRowTotalAttribute(): float
    {
        return round(collect(Timesheet::DAYS)->sum(fn ($day) => (float) $this->{"{$day}_hours"}), 2);
    }
}
