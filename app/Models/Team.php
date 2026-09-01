<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'team_head_id',
    ];

    /**
     * Get the team head (user who leads this team)
     */
    public function teamHead()
    {
        return $this->belongsTo(User::class, 'team_head_id');
    }

    /**
     * Get all users that belong to this team
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}