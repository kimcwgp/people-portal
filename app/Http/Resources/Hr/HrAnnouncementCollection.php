<?php

namespace App\Http\Resources\Hr;

use Illuminate\Http\Resources\Json\ResourceCollection;

class HrAnnouncementCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => HrAnnouncementResource::collection($this->collection),
            'meta' => [
                'total' => $this->collection->count(),
                'can_manage' => true, 
            ],
        ];
    }
}