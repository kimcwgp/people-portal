<?php

namespace App\Http\Resources\Clients;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'parent_company' => $this->parent_company,
            'contact_name' => $this->contact_name,
            'contact_number' => $this->contact_number,
            'description' => $this->description,
            'projects_count' => $this->when(
                isset($this->projects_count),
                $this->projects_count ?? 0
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
