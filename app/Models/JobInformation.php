<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobInformation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'position_name',
        'position_level',
        'career_level',
        'career_band',
        'career_zone',
        'tenure_in_company',
        'tenure_in_role',
        'manager_id',
        'is_manager',
        'effective_date',
        'end_date',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_manager' => 'boolean',
        'tenure_in_company' => 'integer',
        'tenure_in_role' => 'integer',
    ];

    /**
     * Get the user that owns the job information
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the manager for this job information
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function scopeCurrent($query)
    {
        return $query->whereNull('end_date');
    }
}
