<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoginToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];
}
