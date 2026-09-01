<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmploymentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'change_type',
        'from_value',
        'to_value',
        'description',
        'effective_date',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    /**
     * Get the user that owns the employment history
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created this history record
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
