<?php

namespace App\Http\Resources\AssociateLog;

use Illuminate\Http\Resources\Json\JsonResource;

class AssociateLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ],
            'entry_details' => $this->entry_details,
            'date' => $this->date ? $this->date->format('M. d, Y') : null,
            'created_by' => $this->creator?->name ?? 'System',
            'created_at' => $this->created_at ? $this->created_at->format('M. d, Y H:i:s') : null,
            'has_attachment' => $this->hasAttachment(),
            'attachment' => $this->when(
                $this->hasAttachment(),
                [
                    'id' => $this->id,
                    'filename' => $this->attachment_name,
                    'download_url' => route('associate-logs.download', $this->id),
                ]
            ),
        ];
    }

    private function hasAttachment()
    {
        return !empty($this->attachment_id) && !empty($this->attachment_name);
    }
}