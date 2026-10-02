<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};

class Client extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'name',
        'parent_company',
        'contact_name',
        'contact_number',
        'description',
        'updated_at'
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function getProjectsCountAttribute()
    {
        if (array_key_exists('projects_count', $this->attributes)) {
            return (int) $this->attributes['projects_count'];
        }

        return $this->projects()->count();
    }
}