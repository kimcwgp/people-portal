<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeType extends Model
{
    use HasFactory;

    /** Allowed tints map 1:1 to the --color-status-* design tokens. */
    public const TINTS = ['green', 'orange', 'purple', 'blue', 'yellow', 'indigo', 'cyan', 'red', 'gray'];

    protected $fillable = ['name', 'tint', 'description', 'is_billable', 'is_active', 'sort_order'];

    protected $casts = [
        'is_billable' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
