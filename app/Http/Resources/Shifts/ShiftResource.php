<?php

namespace App\Http\Resources\Shifts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_type' => $this->shift_type,
            'start_time' => $this->start_time->format('g:i A'),
            'end_time' => $this->end_time->format('g:i A'),
            'users_count' => $this->when(
                isset($this->users_count),
                $this->users_count ?? 0
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
