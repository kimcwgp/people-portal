<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use HasFactory, SoftDeletes;

    public const CALENDAR_PARTNERS = 'partners';
    public const CALENDAR_PEOPLE = 'people';

    protected $fillable = [
        'name',
        'date',
        'calendar',
        'type',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopeForCalendar(Builder $query, ?string $calendar): Builder
    {
        return $calendar ? $query->where('calendar', $calendar) : $query;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'special_non_working' => 'Special (Non-Working)',
            'company' => 'Company Holiday',
            default => 'Regular Holiday',
        };
    }
}
