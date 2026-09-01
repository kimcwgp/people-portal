<?php

namespace App\Http\Controllers;

use App\Http\Resources\OvertimeResource;
use App\Models\Overtime;
use App\Traits\HasPagination;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeamOvertimeController extends Controller
{
    use HasPagination;

    private function getStandardRelationships(): array
    {
        return [
            'user:id,name,email',
            'project:id,project_name',
            'approver:id,name,email',
            'projectManager:id,name,email'
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPageLimit('team_overtime', $request->get('per_page'));
        
        $query = Overtime::query()
            ->with($this->getStandardRelationships())
            ->where('approver_id', auth()->id());

        if ($request->status === 'CANCELLED') {
            $query = $query->onlyTrashed()->where('status', 'CANCELLED');
        } elseif ($request->status) {
            $query = $query->where('status', $request->status);
        } else {
            $query = $query->withTrashed();
        }

        $overtime = $query
            ->when($request->user_name, function($q, $name) {
                $q->whereHas('user', function($userQuery) use ($name) {
                    $userQuery->where('name', 'LIKE', "%{$name}%");
                });
            })
            ->when($request->start_date, function($q, $startDate) {
                $q->whereDate('ot_date', '>=', $startDate);
            })
            ->when($request->end_date, function($q, $endDate) {
                $q->whereDate('ot_date', '<=', $endDate);
            })
            ->latest('ot_date')
            ->latest('created_at')
            ->paginate($perPage);

        $paginationData = $this->buildPaginationResponse($overtime);
        
        return response()->json([
            'data' => OvertimeResource::collection($overtime->items()),
            'meta' => $paginationData
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        $approverId = auth()->id();

        $baseQuery = Overtime::where('approver_id', $approverId);
        
        // Apply date filters if provided
        if ($request->start_date) {
            $baseQuery = $baseQuery->whereDate('ot_date', '>=', $request->start_date);
        }
        
        if ($request->end_date) {
            $baseQuery = $baseQuery->whereDate('ot_date', '<=', $request->end_date);
        }

        // Apply user name filter if provided
        if ($request->user_name) {
            $baseQuery = $baseQuery->whereHas('user', function($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->user_name}%");
            });
        }

        $stats = [
            'approved' => (clone $baseQuery)->where('status', 'APPROVED')->count(),
            'pending' => (clone $baseQuery)->where('status', 'PENDING')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'REJECTED')->count(),
            'cancelled' => Overtime::onlyTrashed()
                ->where('approver_id', $approverId)
                ->where('status', 'CANCELLED')
                ->when($request->start_date, fn($q) => $q->whereDate('ot_date', '>=', $request->start_date))
                ->when($request->end_date, fn($q) => $q->whereDate('ot_date', '<=', $request->end_date))
                ->when($request->user_name, fn($q) => $q->whereHas('user', fn($userQuery) => $userQuery->where('name', 'LIKE', "%{$request->user_name}%")))
                ->count(),
            'total' => Overtime::withTrashed()
                ->where('approver_id', $approverId)
                ->when($request->start_date, fn($q) => $q->whereDate('ot_date', '>=', $request->start_date))
                ->when($request->end_date, fn($q) => $q->whereDate('ot_date', '<=', $request->end_date))
                ->when($request->user_name, fn($q) => $q->whereHas('user', fn($userQuery) => $userQuery->where('name', 'LIKE', "%{$request->user_name}%")))
                ->count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
        'status' => 'sometimes|in:PENDING,APPROVED,REJECTED,CANCELLED',
        'user_name' => 'sometimes|string',
        'start_date' => 'sometimes|date',
        'end_date' => 'sometimes|date|after_or_equal:start_date',
    ]);
    
    $query = Overtime::with($this->getStandardRelationships())
        ->where('approver_id', auth()->id());

    if ($request->status === 'CANCELLED') {
        $query = $query->onlyTrashed()->where('status', 'CANCELLED');
    } elseif ($request->status) {
        $query = $query->where('status', $request->status);
    } else {
        $query = $query->withTrashed();
    }

    $overtime = $query
        ->when($request->user_name, function($q, $name) {
            $q->whereHas('user', function($userQuery) use ($name) {
                $userQuery->where('name', 'LIKE', "%{$name}%");
            });
        })
        ->when($request->start_date, function($q, $startDate) {
            $q->whereDate('ot_date', '>=', $startDate);
        })
        ->when($request->end_date, function($q, $endDate) {
            $q->whereDate('ot_date', '<=', $endDate);
        })
        ->latest('ot_date')
        ->latest('created_at')
        ->get();
        
        $exportData = $overtime->map(function ($ot) {
            $resource = new OvertimeResource($ot);
            $data = $resource->toArray(request());
            
            return [
                'Employee Name' => $data['user']['name'] ?? 'N/A',
                'Email' => $data['user']['email'] ?? 'N/A',
                'Project' => $data['project']['name'] ?? 'No Project',
                'Overtime Date' => $ot->ot_date ? Carbon::parse($ot->ot_date)->format('Y-m-d') : '',
                'Time In' => $data['time_in'] ?? '',
                'Time Out' => $data['time_out'] ?? '',
                'Hours' => $data['formatted_ot_hours'] ?? '0 hours',
                'Status' => ucfirst($data['status'] ?? ''),
                'Notes' => $data['notes'] ?? '',
                'Submitted At' => $ot->created_at ? Carbon::parse($ot->created_at)->format('Y-m-d H:i:s') : '',
                'Rejection/Cancellation Note' => $data['rejection_note'] ?? $data['cancellation_reason'] ?? '',
                'Cancelled At' => $data['formatted_cancelled_at'] ?? '',
            ];
        });
        
        $filename = 'team_overtime';
        if ($request->status) {
            $filename .= '_' . strtolower($request->status);
        }
        if ($request->start_date || $request->end_date) {
            $startDate = $request->start_date ?? 'all';
            $endDate = $request->end_date ?? 'all';
            $filename .= '_' . $startDate . '_to_' . $endDate;
        } else {
            $filename .= '_all_dates';
        }
        $filename .= '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
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