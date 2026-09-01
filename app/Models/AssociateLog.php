<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssociateLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'date',
        'entry_details',
        'attachment_id',
        'attachment_name',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasAttachment(): bool
    {
        return !empty($this->attachment_id) && !empty($this->attachment_name);
    }

    public function getFileExtension(): ?string
    {
        return $this->attachment_name ? pathinfo($this->attachment_name, PATHINFO_EXTENSION) : null;
    }

    public function getDownloadUrlAttribute(): ?string
    {
        return $this->hasAttachment() ? route('associate-logs.download', $this->id) : null;
    }
}