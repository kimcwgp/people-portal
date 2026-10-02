<?php

namespace App\Http\Resources\Holidays;

use Illuminate\Http\Resources\Json\JsonResource;

class HolidayResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'date' => $this->date?->toDateString(),
            'date_label' => $this->date?->format('M. d, Y'),
            'day_label' => $this->date?->format('l'),
            'calendar' => $this->calendar,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_by' => $this->creator?->name,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
