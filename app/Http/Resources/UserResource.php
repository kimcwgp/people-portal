<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'glip_url' => $this->glip_url,
            'status' => $this->status,
            'team_id' => $this->team_id,
            'shift_id' => $this->shift_id,
            'immediate_sup_id' => $this->immediate_sup_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'initials' => $this->getInitials(),
            'status_text' => $this->status ? 'Active' : 'Inactive',
            'status_color' => $this->status ? 'emerald' : 'amber',

            'team' => $this->whenLoaded('team', function () {
                return [
                    'id' => $this->team->id,
                    'name' => $this->team->name,
                ];
            }),
            
            'immediate_supervisor' => $this->whenLoaded('immediateSupervisor', function () {
                return [
                    'id' => $this->immediateSupervisor->id,
                    'name' => $this->immediateSupervisor->name,
                    'email' => $this->immediateSupervisor->email,
                ];
            }),
            
            'direct_subordinates' => $this->whenLoaded('directSubordinates', function () {
                return $this->directSubordinates->map(function ($subordinate) {
                    return [
                        'id' => $subordinate->id,
                        'name' => $subordinate->name,
                        'email' => $subordinate->email,
                    ];
                });
            }),

            'shift' => $this->whenLoaded('shift', function () {
                return [
                    'id' => $this->shift->id,
                    'shift_type' => $this->shift->shift_type,
                    'start_time' => $this->shift->start_time?->format('g:i A'),
                    'end_time' => $this->shift->end_time?->format('g:i A'),
                    'label' => ucfirst($this->shift->shift_type) . ' (' .
                               $this->shift->start_time?->format('g:i A') . ' - ' .
                               $this->shift->end_time?->format('g:i A') . ')',
                ];
            }),

            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                });
            }),
        ];
    }

    private function getInitials(): string
    {
        return collect(explode(' ', $this->name))
            ->map(fn($name) => strtoupper(substr($name, 0, 1)))
            ->take(2)
            ->implode('');
    }
}