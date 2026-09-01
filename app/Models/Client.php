<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'name',
        'parent_company',
        'contact_name',
        'contact_number',
        'updated_at'
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function getProjectsCountAttribute()
    {
        // Check if projects_count was already loaded via withCount/loadCount
        if (array_key_exists('projects_count', $this->attributes)) {
            return (int) $this->attributes['projects_count'];
        }

        // Fallback - avoid using this in loops
        return $this->projects()->count();
    }
}