<?php

namespace App\Http\Controllers;

use App\Http\Resources\StandupResource;
use App\Models\Standup;
use App\Traits\HasPagination;
use Illuminate\Http\{JsonResponse, Request};
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeamStandupController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPageLimit('team_standups', $request->get('per_page'));
        
        $standups = Standup::query()
            ->with(['user:id,name,email', 'project:id,project_name'])
            ->whereHas('user', fn($q) => $q->where('immediate_sup_id', auth()->id()))
            ->when($request->user_name, fn($q, $name) => 
                $q->whereHas('user', fn($userQuery) => $userQuery->where('name', 'LIKE', "%{$name}%"))
            )
            ->when($request->start_date, fn($q, $startDate) => 
                $q->whereDate('standup_date', '>=', $startDate)
            )
            ->when($request->end_date, fn($q, $endDate) => 
                $q->whereDate('standup_date', '<=', $endDate)
            )
            ->orderBy('standup_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $paginationData = $this->buildPaginationResponse($standups);
        
        return response()->json([
            'data' => StandupResource::collection($standups->items()),
            'meta' => $paginationData
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'user_name' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);
        
        if (($filters['start_date'] ?? null) || ($filters['end_date'] ?? null)) {
            $startDate = Carbon::parse($filters['start_date'] ?? now()->startOfMonth());
            $endDate = Carbon::parse($filters['end_date'] ?? now()->endOfMonth());
        } else if (!empty($filters['user_name'])) {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
        } else {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
        }
        
        $standups = Standup::query()
            ->with(['user:id,name,email', 'project:id,project_name'])
            ->whereHas('user', fn($q) => $q->where('immediate_sup_id', auth()->id()))
            ->when($request->user_name, fn($q, $name) => 
                $q->whereHas('user', fn($userQuery) => $userQuery->where('name', 'LIKE', "%{$name}%"))
            )
            ->whereDate('standup_date', '>=', $startDate)
            ->whereDate('standup_date', '<=', $endDate)
            ->orderBy('standup_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $exportData = $standups->map(function ($standup) {
            $resource = new StandupResource($standup);
            $data = $resource->toArray(request());
            
            return [
                'Employee Name' => $data['user']['name'] ?? 'N/A',
                'Email' => $data['user']['email'] ?? 'N/A',
                'Standup Date' => $standup->standup_date->format('Y-m-d'),
                'Day' => $standup->standup_date->format('l'),
                'Project' => $data['project']['project_name'] ?? 'No Project',
                'Notes' => $data['notes'] ?? '',
                'Impediments' => $data['impediments'] ?? '',
                'Time Spent' => $data['formatted_time'] ?? 'No time tracked',
                'Has Impediments' => $data['has_impediments'] ? 'Yes' : 'No',
                'Submitted At' => $standup->created_at->format('Y-m-d H:i:s'),
                'Relative Time' => $data['relative_date'] ?? '',
            ];
        });
        
        $filename = 'team_standups_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function () use ($exportData) {
            $file = fopen('php://output', 'w');
            
            if ($exportData->isNotEmpty()) {
                fputcsv($file, array_keys($exportData->first()));
            }
            
            foreach ($exportData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}