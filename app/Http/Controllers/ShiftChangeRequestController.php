<?php

namespace App\Http\Controllers;

use App\Models\ShiftChangeRequest;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShiftChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        $requests = ShiftChangeRequest::with(['currentShift', 'requestedShift', 'approver'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'current_shift' => $req->currentShift ? [
                        'id' => $req->currentShift->id,
                        'shift_type' => $req->currentShift->shift_type,
                        'shift_label' => $req->currentShift->shift_label,
                    ] : null,
                    'requested_shift' => [
                        'id' => $req->requestedShift->id,
                        'shift_type' => $req->requestedShift->shift_type,
                        'shift_label' => $req->requestedShift->shift_label,
                    ],
                    'effective_date' => $req->effective_date->format('Y-m-d'),
                    'reason' => $req->reason,
                    'status' => $req->status,
                    'status_label' => $req->status_label,
                    'approver' => $req->approver ? $req->approver->name : null,
                    'approver_notes' => $req->approver_notes,
                    'approved_at' => $req->approved_at?->format('M d, Y g:i A'),
                    'created_at' => $req->created_at->format('M d, Y g:i A'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function getAvailableShifts()
    {
        $shifts = Shift::all()->map(function ($shift) {
            return [
                'id' => $shift->id,
                'shift_type' => $shift->shift_type,
                'shift_label' => $shift->shift_label,
                'start_time' => $shift->start_time ? $shift->start_time->format('g:i A') : null,
                'end_time' => $shift->end_time ? $shift->end_time->format('g:i A') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $shifts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'requested_shift_id' => 'required|exists:shifts,id',
            'reason' => 'required|string|max:500',
        ]);

        $user = Auth::user();
        $today = Carbon::today();
        $lastDayOfMonth = Carbon::now()->endOfMonth()->startOfDay();

        if ($today->greaterThanOrEqualTo($lastDayOfMonth)) {
            return response()->json([
                'success' => false,
                'message' => 'Shift change requests for next month must be submitted before the end of the current month.',
            ], 422);
        }

        if (!$user->shift_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have a current shift assigned. Please contact HR to assign you a shift before requesting a change.',
            ], 422);
        }

        if (!$user->immediate_sup_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have an assigned supervisor to approve your shift change request. Please contact HR.',
            ], 422);
        }

        $hasPending = ShiftChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending shift change request. Please wait for it to be processed.',
            ], 422);
        }

        $effectiveDate = Carbon::now()->addMonth()->startOfMonth();

        $shiftChangeRequest = ShiftChangeRequest::create([
            'user_id' => $user->id,
            'current_shift_id' => $user->shift_id,
            'requested_shift_id' => $request->requested_shift_id,
            'effective_date' => $effectiveDate,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift change request submitted successfully. The change will be effective on ' . $effectiveDate->format('F 1, Y') . '. Your supervisor will review your request.',
            'data' => $shiftChangeRequest,
        ], 201);
    }

    public function getPendingRequests()
    {
        $userId = Auth::id();

        $requests = ShiftChangeRequest::with(['user', 'currentShift', 'requestedShift'])
            ->pending()
            ->forApprover($userId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'user' => [
                        'id' => $req->user->id,
                        'name' => $req->user->name,
                        'email' => $req->user->email,
                    ],
                    'current_shift' => $req->currentShift ? [
                        'id' => $req->currentShift->id,
                        'shift_type' => $req->currentShift->shift_type,
                        'shift_label' => $req->currentShift->shift_label,
                    ] : null,
                    'requested_shift' => [
                        'id' => $req->requestedShift->id,
                        'shift_type' => $req->requestedShift->shift_type,
                        'shift_label' => $req->requestedShift->shift_label,
                    ],
                    'effective_date' => $req->effective_date->format('M d, Y'),
                    'reason' => $req->reason,
                    'status' => $req->status,
                    'created_at' => $req->created_at->format('M d, Y'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function approve(Request $request, ShiftChangeRequest $shiftChangeRequest)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $shiftChangeRequest->load('user');
        $user = Auth::user();

        // Check authorization - Super Admins can approve, or must be the direct supervisor
        if (!$user->hasRole('Super Admin') && $shiftChangeRequest->user->immediate_sup_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to approve this request.',
            ], 403);
        }

        if (!$shiftChangeRequest->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been processed.',
            ], 422);
        }

        $shiftChangeRequest->update([
            'status' => 'approved',
            'approver_id' => $user->id,
            'approver_notes' => $request->notes,
            'approved_at' => now(),
        ]);

        $effectiveDate = $shiftChangeRequest->effective_date->format('F 1, Y');

        return response()->json([
            'success' => true,
            'message' => "Shift change request approved. The shift will be automatically updated on {$effectiveDate}.",
        ]);
    }

    public function reject(Request $request, ShiftChangeRequest $shiftChangeRequest)
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $shiftChangeRequest->load('user');
        $user = Auth::user();

        // Check authorization - Super Admins can reject, or must be the direct supervisor
        if (!$user->hasRole('Super Admin') && $shiftChangeRequest->user->immediate_sup_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this request.',
            ], 403);
        }

        if (!$shiftChangeRequest->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been processed.',
            ], 422);
        }

        $shiftChangeRequest->update([
            'status' => 'rejected',
            'approver_id' => $user->id,
            'approver_notes' => $request->notes,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift change request rejected.',
        ]);
    }

    public function getTeamRequests(Request $request)
    {
        $userId = Auth::id();

        $status = $request->input('status', 'all');
        $userName = $request->input('userName');
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');

        $query = ShiftChangeRequest::with(['user', 'currentShift', 'requestedShift', 'approver'])
            ->forApprover($userId);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($userName) {
            $query->whereHas('user', function ($q) use ($userName) {
                $q->where('name', 'LIKE', "%{$userName}%");
            });
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        }

        // Get status counts
        $totalQuery = ShiftChangeRequest::forApprover($userId);
        if ($userName) {
            $totalQuery->whereHas('user', function ($q) use ($userName) {
                $q->where('name', 'LIKE', "%{$userName}%");
            });
        }
        if ($startDate && $endDate) {
            $totalQuery->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59']);
        }

        $statusCounts = [
            'all' => $totalQuery->count(),
            'pending' => (clone $totalQuery)->pending()->count(),
            'approved' => (clone $totalQuery)->approved()->count(),
            'rejected' => (clone $totalQuery)->rejected()->count(),
        ];

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($req) {
                return [
                    'id' => $req->id,
                    'user' => [
                        'id' => $req->user->id,
                        'name' => $req->user->name,
                        'email' => $req->user->email,
                    ],
                    'current_shift' => $req->currentShift ? [
                        'id' => $req->currentShift->id,
                        'shift_type' => $req->currentShift->shift_type,
                        'shift_label' => $req->currentShift->shift_label,
                        'start_time' => $req->currentShift->start_time?->format('H:i'),
                        'end_time' => $req->currentShift->end_time?->format('H:i'),
                    ] : null,
                    'requested_shift' => [
                        'id' => $req->requestedShift->id,
                        'shift_type' => $req->requestedShift->shift_type,
                        'shift_label' => $req->requestedShift->shift_label,
                        'start_time' => $req->requestedShift->start_time?->format('H:i'),
                        'end_time' => $req->requestedShift->end_time?->format('H:i'),
                    ],
                    'effective_date' => $req->effective_date->format('Y-m-d'),
                    'formatted_effective_date' => $req->effective_date->format('M d, Y'),
                    'reason' => $req->reason,
                    'status' => $req->status,
                    'approver' => $req->approver ? $req->approver->name : null,
                    'approver_notes' => $req->approver_notes,
                    'approved_at' => $req->approved_at?->format('M d, Y g:i A'),
                    'created_at' => $req->created_at->format('M d, Y'),
                    'relative_date' => $req->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => array_merge($requests->toArray(), [
                'status_counts' => $statusCounts,
            ]),
        ]);
    }

    public function exportTeamRequests(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'status' => 'sometimes|in:all,pending,approved,rejected',
            'userName' => 'sometimes|string',
            'startDate' => 'sometimes|date',
            'endDate' => 'sometimes|date|after_or_equal:startDate',
        ]);

        $userId = Auth::id();

        $query = ShiftChangeRequest::with(['user', 'currentShift', 'requestedShift', 'approver'])
            ->forApprover($userId);

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['userName'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['userName']}%");
            });
        }

        if (isset($filters['startDate']) && isset($filters['endDate'])) {
            $query->whereBetween('created_at', [$filters['startDate'], $filters['endDate'] . ' 23:59:59']);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        $exportData = $requests->map(function ($req) {
            return [
                'Employee Name' => $req->user->name ?? 'N/A',
                'Email' => $req->user->email ?? 'N/A',
                'Current Shift' => $req->currentShift ? 
                    ($req->currentShift->shift_label ?? $req->currentShift->shift_type) : 'None',
                'Current Shift Time' => $req->currentShift ? 
                    ($req->currentShift->start_time?->format('H:i') . ' - ' . $req->currentShift->end_time?->format('H:i')) : '',
                'Requested Shift' => $req->requestedShift->shift_label ?? $req->requestedShift->shift_type ?? 'Unknown',
                'Requested Shift Time' => $req->requestedShift ? 
                    ($req->requestedShift->start_time?->format('H:i') . ' - ' . $req->requestedShift->end_time?->format('H:i')) : '',
                'Effective Date' => $req->effective_date ? $req->effective_date->format('Y-m-d') : '',
                'Reason' => $req->reason ?? '',
                'Status' => ucfirst($req->status ?? ''),
                'Approver' => $req->approver->name ?? '',
                'Approver Notes' => $req->approver_notes ?? '',
                'Submitted At' => $req->created_at ? $req->created_at->format('Y-m-d H:i:s') : '',
                'Approved/Rejected At' => $req->approved_at ? $req->approved_at->format('Y-m-d H:i:s') : '',
            ];
        });

        $statusFilter = $filters['status'] ?? 'all';
        $filename = 'team_shift_changes_' . $statusFilter . '_' . date('Y-m-d') . '.csv';

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
