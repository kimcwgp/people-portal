<?php

namespace App\Http\Resources\Hr;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class HrAnnouncementResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->image ? Storage::url($this->image) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->user->name ?? 'HR Department',
            'formatted_date' => $this->created_at->format('M j, Y g:i A'),
            
            // Simple image check
            'has_image' => !empty($this->image),
        ];
    }
}