<?php

namespace App\Http\Controllers;

use App\Http\Resources\LeaveResource;
use App\Models\Leave;
use App\Traits\HasPagination;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, StreamedResponse};

class TeamLeavesController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPageLimit('team_leaves', $request->get('per_page'));
        
        $query = Leave::query()
            ->with(['user:id,name,email', 'leaveType'])
            ->whereHas('user', fn($q) => $q->where('immediate_sup_id', auth()->id()));

        if ($request->status === 'cancelled') {
            $query = $query->onlyTrashed()->where('status', 'cancelled');
        } elseif ($request->status) {
            $query = $query->where('status', $request->status);
        } else {
            // When no status filter (All), include trashed (cancelled) leaves
            $query = $query->withTrashed();
        }

        $leaves = $query
            ->when($request->user_name, fn($q, $name) => 
                $q->whereHas('user', fn($userQuery) => $userQuery->where('name', 'LIKE', "%{$name}%"))
            )
            ->when($request->start_date, fn($q, $startDate) => $q->whereDate('start_date', '>=', $startDate))
            ->when($request->end_date, fn($q, $endDate) => $q->whereDate('end_date', '<=', $endDate))
            ->latest('created_at')
            ->paginate($perPage);

        $paginationData = $this->buildPaginationResponse($leaves);
        
        return response()->json([
            'data' => LeaveResource::collection($leaves->items()),
            'meta' => $paginationData
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $baseQuery = Leave::whereHas('user', fn($q) => $q->where('immediate_sup_id', $userId));
        
        // Apply date filters if provided
        if ($request->start_date) {
            $baseQuery = $baseQuery->whereDate('start_date', '>=', $request->start_date);
        }
        
        if ($request->end_date) {
            $baseQuery = $baseQuery->whereDate('end_date', '<=', $request->end_date);
        }

        // Apply user name filter if provided
        if ($request->user_name) {
            $baseQuery = $baseQuery->whereHas('user', function($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->user_name}%");
            });
        }

        $stats = [
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'cancelled' => Leave::onlyTrashed()
                ->whereHas('user', fn($q) => $q->where('immediate_sup_id', $userId))
                ->where('status', 'cancelled')
                ->when($request->start_date, fn($q) => $q->whereDate('start_date', '>=', $request->start_date))
                ->when($request->end_date, fn($q) => $q->whereDate('end_date', '<=', $request->end_date))
                ->when($request->user_name, fn($q) => $q->whereHas('user', fn($userQuery) => $userQuery->where('name', 'LIKE', "%{$request->user_name}%")))
                ->count(),
            'total' => Leave::withTrashed()
                ->whereHas('user', fn($q) => $q->where('immediate_sup_id', $userId))
                ->when($request->start_date, fn($q) => $q->whereDate('start_date', '>=', $request->start_date))
                ->when($request->end_date, fn($q) => $q->whereDate('end_date', '<=', $request->end_date))
                ->when($request->user_name, fn($q) => $q->whereHas('user', fn($userQuery) => $userQuery->where('name', 'LIKE', "%{$request->user_name}%")))
                ->count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'status' => 'sometimes|in:pending,approved,rejected,cancelled',
            'user_name' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);
        
        $query = Leave::with(['user:id,name,email', 'leaveType:id,name'])
            ->whereHas('user', fn($q) => $q->where('immediate_sup_id', auth()->id()));

        if ($request->status === 'cancelled') {
            $query = $query->onlyTrashed()->where('status', 'cancelled');
        } else {
            if ($request->status) {
                $query = $query->where('status', $request->status);
            }
        }

        $leaves = $query
            ->when($request->user_name, fn($q, $name) => 
                $q->whereHas('user', fn($userQuery) => $userQuery->where('name', 'LIKE', "%{$name}%"))
            )
            ->when($request->start_date, fn($q, $startDate) => $q->whereDate('start_date', '>=', $startDate))
            ->when($request->end_date, fn($q, $endDate) => $q->whereDate('end_date', '<=', $endDate))
            ->latest('created_at')
            ->get();
        
        $exportData = $leaves->map(function ($leave) {
            $resource = new LeaveResource($leave);
            $data = $resource->toArray(request());
            
            return [
                'Employee Name' => $data['user']['name'] ?? 'N/A',
                'Email' => $data['user']['email'] ?? 'N/A',
                'Leave Type' => $data['leave_type']['name'] ?? 'N/A',
                'Start Date' => $leave->start_date ? Carbon::parse($leave->start_date)->format('Y-m-d') : '',
                'End Date' => $leave->end_date ? Carbon::parse($leave->end_date)->format('Y-m-d') : '',
                'Duration' => $data['duration'] ?? 'All Day',
                'Time In' => $data['time_in'] ?? '',
                'Time Out' => $data['time_out'] ?? '',
                'Reason' => $data['reason'] ?? '',
                'Status' => ucfirst($data['status'] ?? ''),
                'Days Count' => $this->calculateLeaveDays($leave->start_date, $leave->end_date),
                'Has Attachment' => $leave->attachment ? 'Yes' : 'No',
                'Submitted At' => $leave->created_at ? Carbon::parse($leave->created_at)->format('Y-m-d H:i:s') : '',
                'Approved/Rejected At' => $leave->approved_at ? Carbon::parse($leave->approved_at)->format('Y-m-d H:i:s') : '',
                'Rejection/Cancellation Note' => $data['rejection_note'] ?? '',
                'Cancelled At' => $leave->cancelled_at ? Carbon::parse($leave->cancelled_at)->format('Y-m-d H:i:s') : '',
            ];
        });
        
        $filename = 'team_leaves';
        if ($request->status) {
            $filename .= '_' . $request->status;
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

    public function downloadAttachment($leaveId): BinaryFileResponse|StreamedResponse
    {
        $leave = Leave::withTrashed()
            ->whereHas('user', fn($q) => $q->where('immediate_sup_id', auth()->id()))
            ->findOrFail($leaveId);

        if (!$leave->attachment) {
            abort(404, 'Attachment not found');
        }

        $filePath = $leave->attachment;
        
        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->file(Storage::disk('public')->path($filePath));
    }

    private function calculateLeaveDays($startDate, $endDate)
    {
        if (!$startDate || !$endDate) return 0;
        
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        return $start->diffInDays($end) + 1;
    }
}