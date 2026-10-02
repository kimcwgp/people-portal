<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timesheet extends Model
{
    use HasFactory, SoftDeletes;

    public const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    protected $fillable = [
        'user_id', 'week_start', 'week_end', 'status',
        'submitted_at', 'approver_id', 'approved_at', 'rejection_note',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected $appends = ['total_hours', 'week_label', 'is_editable'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Monday of the week containing $date. */
    public static function weekStartFor($date): Carbon
    {
        return Carbon::parse($date)->startOfWeek(Carbon::MONDAY);
    }

    public static function forUserAndWeek(int $userId, $date): self
    {
        $start = self::weekStartFor($date);

        return self::firstOrCreate(
            ['user_id' => $userId, 'week_start' => $start->toDateString()],
            ['week_end' => $start->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(), 'status' => 'draft']
        );
    }

    public function getTotalHoursAttribute(): float
    {
        if (! $this->relationLoaded('entries')) {
            return round((float) $this->entries()->get()->sum->row_total, 2);
        }

        return round((float) $this->entries->sum->row_total, 2);
    }

    /** Hours per weekday, for the column totals above the grid. */
    public function dailyTotals(): array
    {
        $entries = $this->relationLoaded('entries') ? $this->entries : $this->entries()->get();

        return collect(self::DAYS)
            ->mapWithKeys(fn ($day) => [$day => round((float) $entries->sum("{$day}_hours"), 2)])
            ->all();
    }

    public function getWeekLabelAttribute(): string
    {
        if (! $this->week_start || ! $this->week_end) {
            return '';
        }

        // "September 9-15, 2026" -- or spell both months when it straddles one.
        if ($this->week_start->month === $this->week_end->month) {
            return $this->week_start->format('F j') . '-' . $this->week_end->format('j, Y');
        }

        return $this->week_start->format('F j') . ' - ' . $this->week_end->format('F j, Y');
    }

    /** Submitted and approved weeks are locked. */
    public function getIsEditableAttribute(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
