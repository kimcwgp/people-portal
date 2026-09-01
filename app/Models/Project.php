<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, Filterable, Sortable;

    protected $fillable = [
        'client_id',
        'project_name',
        'project_type_id',
        'clickup_url',
        'pm_id',
        'glip_url',
        'description',
    ];

    protected $sortable = [
        'id',
        'project_name',
        'created_at',
        'updated_at',
        'client_id',
        'project_type_id'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pm_id');
    }

    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
    }

    public function overtimes(): HasMany
    {
        return $this->hasMany(Overtime::class);
    }

    public function standups(): HasMany
    {
        return $this->hasMany(Standup::class);
    }
}