<?php

namespace App\Http\Resources\TimeEntries;

use Illuminate\Http\Resources\Json\JsonResource;

class TimeTypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tint' => $this->tint,
            'description' => $this->description,
            'is_billable' => $this->is_billable,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
