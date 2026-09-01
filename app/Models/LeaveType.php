<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory, Filterable, Sortable;

    protected $table = 'leaves_type';

    protected $fillable = [
        'name',
        'type',
    ];

    protected $sortable = [
        'id',
        'name',
        'type',
        'created_at',
        'updated_at',
    ];

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'leaves_type_id');
    }
}