<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonalInformation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'gender',
        'marital_status',
        'spouse_name',
        'num_children',
        'phone_number',
        'alternate_phone_number',
        'permanent_address',
        'current_address',
        'emergency_contact_name',
        'emergency_contact_number',
        'emergency_contact_relationship',
        'tin',
        'sss',
        'philhealth',
        'pagibig',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'num_children' => 'integer',
    ];

    /**
     * Get the user that owns the personal information
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

