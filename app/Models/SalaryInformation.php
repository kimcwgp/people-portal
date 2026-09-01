<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryInformation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'salary',
        'currency',
        'pay_frequency',
        'effective_date',
        'end_date',
        'allowances',
        'bonuses',
        'approved_by',
        'is_archived',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_archived' => 'boolean',
    ];

    /**
     * Get the user that owns the salary information
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved this salary
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('end_date');
    }
}
