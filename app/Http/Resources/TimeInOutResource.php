<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimeInOutResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'success' => true,
            'message' => $this->resource['message'] ?? 'Action completed successfully',
            'status' => $this->resource['status'] ?? null,
            'data' => [
                'attendance' => $this->resource['attendance'] ?? null,
                'time_in' => $this->resource['time_in'] ?? null,
                'time_out' => $this->resource['time_out'] ?? null,
                'action_timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    public function with($request): array
    {
        return [
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'action_type' => $this->resource['status'] ?? 'unknown',
                'user_id' => auth()->id(),
            ]
        ];
    }

    public function withResponse($request, $response): void
    {
        $response->header('X-Action-Time', now()->toIso8601String());
        
        $status = $this->resource['status'] ?? null;
        
        switch ($status) {
            case 'TIME_IN':
            case 'TIME_OUT':
                $response->setStatusCode(200);
                break;
            case 'ALREADY_OUT':
                $response->setStatusCode(409); // Conflict
                break;
            default:
                $response->setStatusCode(200);
        }
    }
}