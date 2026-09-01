<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_id',
        'hire_date',
        'regularization_date',
        'resignation_date',
        'employee_status',
        'employment_status',
        'employment_type',
        'termination_reason',
        'termination_notes',
        'terminated_by',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'regularization_date' => 'date',
        'resignation_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function terminatedBy()
    {
        return $this->belongsTo(User::class, 'terminated_by');
    }

    public function scopeRegular($query)
    {
        return $query->where('employment_status', 'Regular');
    }

    public function scopeProbationary($query)
    {
        return $query->where('employment_status', 'Probationary');
    }

    public function isRegular(): bool
    {
        return $this->employment_status === 'Regular';
    }
}