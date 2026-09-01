<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForApproval extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'approver_id',
        'approved_by',
        'type_of_approval',
        'details',
        'other_details',
        'status',
        'time_in',
        'time_out',
        'approved_at',
    ];

    protected $casts = [
        'time_in' => 'datetime',
        'time_out' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the user who submitted the approval request
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who should approve this request
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
