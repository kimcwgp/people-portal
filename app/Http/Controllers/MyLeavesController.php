<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Http\Resources\LeaveResource;
use App\Http\Resources\LeaveTypeResource;
use App\Http\Requests\StoreLeaveRequest;
use App\Http\Requests\UpdateLeaveRequest;
use App\Traits\HasPagination;
use Illuminate\Http\JsonResponse;

class MyLeavesController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPageLimit('my_leaves', $request->get('per_page'));
        $query = Leave::with(['leaveType','approver:id,name,email'])
            ->where('user_id', Auth::id());

        if ($request->status === 'cancelled') {
            $query = $query->onlyTrashed()->where('status', 'cancelled');
        } elseif ($request->status) {
            $query = $query->where('status', $request->status);
        } else {
            // When no status filter (All), include trashed (cancelled) leaves
            $query = $query->withTrashed();
        }

        $leaves = $query
            ->when($request->leave_type_id, fn($q, $typeId) => 
                $q->where('leaves_type_id', $typeId)
            )
            ->when($request->start_date, fn($q, $startDate) => 
                $q->whereDate('start_date', '>=', $startDate)
            )
            ->when($request->end_date, fn($q, $endDate) => 
                $q->whereDate('end_date', '<=', $endDate)
            )
            ->latest()
            ->paginate($perPage);

        $paginationData = $this->buildPaginationResponse($leaves);
        
        return response()->json([
            'data' => LeaveResource::collection($leaves->items()),
            'meta' => $paginationData
        ]);
    }

    public function getLeaveTypes(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $types = LeaveType::all();
        return LeaveTypeResource::collection($types)
            ->additional(['success' => true]);
    }

    public function store(StoreLeaveRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();
        $data['status']  = 'pending';

        $user = Auth::user();
        if (!$user->immediate_sup_id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have an assigned supervisor to approve your leave request. Please contact HR.',
            ], 422);
        }
        
        $data['approver_id'] = $user->immediate_sup_id;

        if (($data['duration'] ?? null) !== 'Custom') {
            $data['time_in'] = null;
            $data['time_out'] = null;
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $data['attachment'] = $file->storeAs(
                'leave_attachments',
                time().'_'.$file->getClientOriginalName(),
                'public'
            );
        }

        $leave = Leave::create($data)->load('leaveType','approver');

        return (new LeaveResource($leave))
            ->additional([
                'success' => true,
                'message' => 'Leave request submitted successfully and sent to your supervisor for approval',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Leave $leave): \Illuminate\Http\JsonResponse
    {
        if ($leave->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $leave->load('leaveType','approver');

        return (new LeaveResource($leave))
            ->additional(['success' => true]);
    }

    public function update(UpdateLeaveRequest $request, Leave $leave): \Illuminate\Http\JsonResponse
    {
        if ($leave->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending leave requests can be updated.',
            ], 422);
        }

        $data = $request->validated();

        if (isset($data['user_id']) && $data['user_id'] != $leave->user_id) {
            $newUser = User::find($data['user_id']);
            if ($newUser && $newUser->immediate_sup_id) {
                $data['approver_id'] = $newUser->immediate_sup_id;
            }
        }

        if (($data['duration'] ?? null) !== 'Custom') {
            $data['time_in'] = null;
            $data['time_out'] = null;
        }

        if ($request->hasFile('attachment')) {
            if ($leave->attachment && Storage::disk('public')->exists($leave->attachment)) {
                Storage::disk('public')->delete($leave->attachment);
            }
            $file = $request->file('attachment');
            $data['attachment'] = $file->storeAs(
                'leave_attachments',
                time().'_'.$file->getClientOriginalName(),
                'public'
            );
        } else {
            $lt = LeaveType::find($data['leaves_type_id'] ?? $leave->leaves_type_id);
            $requiresMC = $lt && str_contains($lt->name, 'WITH Medical Certificate');
            if (! $requiresMC && $leave->attachment) {
                if (Storage::disk('public')->exists($leave->attachment)) {
                    Storage::disk('public')->delete($leave->attachment);
                }
                $data['attachment'] = null;
            }
        }

        $leave->update($data);
        $leave->load('leaveType','approver');

        return (new LeaveResource($leave))
            ->additional([
                'success' => true,
                'message' => 'Leave request updated successfully',
            ]);
    }

    public function destroy(Leave $leave): JsonResponse
    {
        if ($leave->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        if ($leave->attachment && Storage::disk('public')->exists($leave->attachment)) {
            Storage::disk('public')->delete($leave->attachment);
        }

        $leave->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave request deleted successfully',
        ]);
    }

    public function cancel(Request $request, Leave $leave): JsonResponse
    {
        if ($leave->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only cancel your own leave requests.',
            ], 403);
        }

        if (!in_array($leave->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or approved leave requests can be cancelled.',
            ], 422);
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        $leave->update([
            'status' => 'cancelled',
            'rejection_note' => $request->cancellation_reason,
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id()
        ]);

        $leave->delete();

        $user = Auth::user();
        if ($user->immediate_sup_id) {
            $supervisor = \App\Models\User::find($user->immediate_sup_id);
            if ($supervisor && $supervisor->glip_url) {
                $ringCentral = app(\App\Services\RingCentralService::class);
                $ringCentral->sendLeaveCancellationNotification(
                    $leave, 
                    $user, 
                    $supervisor->glip_url, 
                    $request->cancellation_reason
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Leave request has been cancelled successfully. Your supervisor has been notified.',
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $baseQuery = Leave::where('user_id', $userId);
        
        // Apply date filters if provided
        if ($request->start_date) {
            $baseQuery = $baseQuery->whereDate('start_date', '>=', $request->start_date);
        }
        
        if ($request->end_date) {
            $baseQuery = $baseQuery->whereDate('end_date', '<=', $request->end_date);
        }

        // Apply leave type filter if provided
        if ($request->leave_type_id) {
            $baseQuery = $baseQuery->where('leaves_type_id', $request->leave_type_id);
        }

        $stats = [
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'pending'  => (clone $baseQuery)->where('status', 'pending')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'cancelled' => Leave::onlyTrashed()
                ->where('user_id', $userId)
                ->where('status', 'cancelled')
                ->when($request->start_date, fn($q) => $q->whereDate('start_date', '>=', $request->start_date))
                ->when($request->end_date, fn($q) => $q->whereDate('end_date', '<=', $request->end_date))
                ->when($request->leave_type_id, fn($q) => $q->where('leaves_type_id', $request->leave_type_id))
                ->count(),
            'total'    => Leave::withTrashed()
                ->where('user_id', $userId)
                ->when($request->start_date, fn($q) => $q->whereDate('start_date', '>=', $request->start_date))
                ->when($request->end_date, fn($q) => $q->whereDate('end_date', '<=', $request->end_date))
                ->when($request->leave_type_id, fn($q) => $q->where('leaves_type_id', $request->leave_type_id))
                ->count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }
}