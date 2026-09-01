<?php

namespace App\Http\Resources\Projects;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_name' => $this->project_name,
            'project_type_id' => $this->project_type_id,
            'description' => $this->description,
            'glip_url' => $this->glip_url,
            'client_id' => $this->client_id, 
            'pm_id' => $this->pm_id,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                ];
            }),
            'project_manager' => $this->whenLoaded('projectManager', function () {
                return [
                    'id' => $this->projectManager->id,
                    'name' => $this->projectManager->name,
                ];
            }),
            'project_type' => $this->whenLoaded('projectType', function () {
                return [
                    'id' => $this->projectType->id,
                    'name' => $this->projectType->name,
                    'description' => $this->projectType->description,
                ];
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}