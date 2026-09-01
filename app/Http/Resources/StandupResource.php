<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StandupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'standup_date' => $this->standup_date ? $this->standup_date->format('Y-m-d') : null,
            'notes' => $this->notes,
            'impediments' => $this->impediments,
            'time_spent_minutes' => $this->time_spent_minutes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            'formatted_standup_date' => $this->standup_date ?
                $this->standup_date->format('M j, Y') : null,
            'relative_date' => $this->created_at ?
                $this->created_at->diffForHumans() : null,

            'formatted_time' => $this->formatTimeSpent(),

            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'project_name' => $this->project->project_name,
                ];
            }),

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'initials' => $this->getUserInitials($this->user->name),
                ];
            }),

            'has_impediments' => !empty($this->impediments),
            'is_recent' => $this->created_at ?
                $this->created_at->isAfter(now()->subDays(7)) : false,
            'is_today' => $this->standup_date ?
                $this->standup_date->isToday() : false,
        ];
    }

    private function getUserInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        $initials = '';
        
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        
        return substr($initials, 0, 2);
    }

    private function formatTimeSpent(): string
    {
        if (empty($this->time_spent_minutes) || $this->time_spent_minutes <= 0) {
            return 'No time tracked';
        }

        $hours = intdiv($this->time_spent_minutes, 60);
        $minutes = $this->time_spent_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }
}