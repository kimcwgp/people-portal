<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{Auth, Log, DB};
use App\Http\Requests\{ClockActionRequest, BreakActionRequest, ApprovalRequest};
use App\Http\Resources\{DashboardResource, TimeInOutResource};
use App\Services\AttendanceService;
use App\Models\{Leave, ShiftChangeRequest, AttendanceCorrection, Timesheet, User};

class DashboardController extends Controller
{
    private AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    private function user(): ?User
    {
        return Auth::user();
    }

    public function index(Request $request): DashboardResource|JsonResponse
    {
        try {
            $dashboardData = $this->attendanceService->getDashboardData($this->user());
            return new DashboardResource($dashboardData);

        } catch (\Exception $e) {
            Log::error('Dashboard load error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data'
            ], 500);
        }
    }

    public function clockInOut(ClockActionRequest $request): TimeInOutResource|JsonResponse
    {
        try {
            $result = $this->attendanceService->toggleClock($this->user(), $request->input('notes'));
            return new TimeInOutResource($result);

        } catch (\Exception $e) {
            Log::error('Clock in/out error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'notes' => $request->input('notes'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function startBreak(BreakActionRequest $request): TimeInOutResource|JsonResponse
    {
        try {
            $type = $request->validated()['type'];
            $notes = $request->input('notes');

            if (!$this->attendanceService->isUserClockedIn($this->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be clocked in to start a break'
                ], 422);
            }

            $result = $this->attendanceService->startBreak($this->user(), $type, $notes);
            return new TimeInOutResource($result);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Start break error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'break_type' => $request->input('type'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function endBreak(BreakActionRequest $request): TimeInOutResource|JsonResponse
    {
        try {
            $type = $request->validated()['type'];
            $notes = $request->input('notes');

            if (!$this->attendanceService->hasActiveBreak($this->user(), $type)) {
                return response()->json([
                    'success' => false,
                    'message' => "No active {$type} break found"
                ], 422);
            }

            $result = $this->attendanceService->endBreak($this->user(), $type, $notes);
            return new TimeInOutResource($result);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('End break error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'break_type' => $request->input('type'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function checkAutoTimeout(Request $request): JsonResponse
    {
        try {
            $shouldTimeout = $this->attendanceService->shouldAutoTimeout($this->user());

            if ($shouldTimeout) {
                $this->attendanceService->performAutoTimeout($this->user());

                return response()->json([
                    'timeout' => true,
                    'message' => 'You have been automatically timed out due to shift end'
                ]);
            }

            return response()->json([
                'timeout' => false,
                'message' => 'No auto timeout required'
            ]);

        } catch (\Exception $e) {
            Log::error('Auto timeout check error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check auto timeout'
            ], 500);
        }
    }

    public function getStatus(Request $request): JsonResponse
    {
        try {
            $status = $this->attendanceService->getCurrentStatus($this->user());
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Status check error: ' . $e->getMessage(), [
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get current status'
            ], 500);
        }
    }

    public function approveLeave(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->user();
            $leave = Leave::with('user')->findOrFail($id);

            DB::transaction(function () use ($leave, $user) {
                $leave->update([
                    'status' => 'approved',
                    'approver_id' => $user->id,
                    'approved_at' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Leave request approved successfully'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to approve this leave request'
            ], 403);

        } catch (\Exception $e) {
            Log::error('Approve leave error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'leave_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve leave request'
            ], 500);
        }
    }

    public function rejectLeave(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->user();
            $leave = Leave::with('user')->findOrFail($id);

            DB::transaction(function () use ($leave, $user, $request) {
                $leave->update([
                    'status' => 'rejected',
                    'approver_id' => $user->id,
                    'rejection_note' => $request->rejection_note,
                    'rejected_at' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Leave request rejected successfully'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this leave request'
            ], 403);

        } catch (\Exception $e) {
            Log::error('Reject leave error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'leave_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject leave request'
            ], 500);
        }
    }

    public function approveShiftChange(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->user();
            $shiftRequest = ShiftChangeRequest::with(['user', 'requestedShift'])->findOrFail($id);

            DB::transaction(function () use ($shiftRequest, $user) {
                $shiftRequest->update([
                    'status' => 'approved',
                    'approver_id' => $user->id,
                    'approved_at' => now(),
                ]);
            });

            $effectiveDate = \Carbon\Carbon::parse($shiftRequest->effective_date)->format('F 1, Y');

            return response()->json([
                'success' => true,
                'message' => "Shift change request approved. The shift will be automatically updated on {$effectiveDate}."
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to approve this shift change request'
            ], 403);

        } catch (\Exception $e) {
            Log::error('Approve shift change error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'shift_request_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve shift change request'
            ], 500);
        }
    }

    public function rejectShiftChange(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->user();
            $shiftRequest = ShiftChangeRequest::with(['user', 'requestedShift'])->findOrFail($id);

            DB::transaction(function () use ($shiftRequest, $user, $request) {
                $shiftRequest->update([
                    'status' => 'rejected',
                    'approver_id' => $user->id,
                    'approver_notes' => $request->rejection_note,
                    'approved_at' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Shift change request rejected successfully'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this shift change request'
            ], 403);

        } catch (\Exception $e) {
            Log::error('Reject shift change error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'shift_request_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                                'success' => false,
                'message' => 'Failed to reject shift change request'
            ], 500);
        }
    }

    public function approveTimesheet(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->user();
            $timesheet = Timesheet::with('user')->findOrFail($id);

            if ($denied = $this->guardTimesheet($timesheet, $user)) {
                return $denied;
            }

            $timesheet->update([
                'status' => 'approved',
                'approver_id' => $user->id,
                'approved_at' => now(),
                'rejection_note' => null,
            ]);

            return response()->json(['success' => true, 'message' => 'Timesheet approved successfully']);

        } catch (\Exception $e) {
            Log::error('Approve timesheet error: ' . $e->getMessage(), [
                'user_id' => Auth::id(), 'timesheet_id' => $id,
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to approve timesheet'], 500);
        }
    }

    public function rejectTimesheet(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->user();
            $timesheet = Timesheet::with('user')->findOrFail($id);

            if ($denied = $this->guardTimesheet($timesheet, $user)) {
                return $denied;
            }

            // Back to the owner as 'rejected', which is editable again.
            $timesheet->update([
                'status' => 'rejected',
                'approver_id' => $user->id,
                'rejection_note' => $request->rejection_note,
                'approved_at' => null,
            ]);

            return response()->json(['success' => true, 'message' => 'Timesheet sent back successfully']);

        } catch (\Exception $e) {
            Log::error('Reject timesheet error: ' . $e->getMessage(), [
                'user_id' => Auth::id(), 'timesheet_id' => $id,
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to reject timesheet'], 500);
        }
    }

    /** Only the owner's supervisor may act, and only while it is pending. */
    private function guardTimesheet(Timesheet $timesheet, ?User $approver): ?JsonResponse
    {
        $isSupervisor = $timesheet->user?->immediate_sup_id === $approver?->id;

        if (! $approver || (! $isSupervisor && ! $approver->hasRole('Super Admin'))) {
            return response()->json([
                'success' => false,
                'message' => 'You are not the approver for this timesheet',
            ], 403);
        }

        if ($timesheet->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "This timesheet is already {$timesheet->status}",
            ], 422);
        }

        return null;
    }

    public function approveAttendanceCorrection(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->user();
            $correction = AttendanceCorrection::with(['user', 'attendance', 'attendance.breaks'])->findOrFail($id);

            DB::transaction(function () use ($correction, $user) {
                $correction->update([
                    'status' => 'approved',
                    'approver_id' => $user->id,
                    'approved_at' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Attendance correction approved successfully'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to approve this attendance correction'
            ], 403);

        } catch (\Exception $e) {
            Log::error('Approve attendance correction error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'correction_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve attendance correction'
            ], 500);
        }
    }

    public function rejectAttendanceCorrection(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->user();
            $correction = AttendanceCorrection::with(['user', 'attendance', 'attendance.breaks'])->findOrFail($id);

            DB::transaction(function () use ($correction, $user, $request) {
                $correction->update([
                    'status' => 'rejected',
                    'approver_id' => $user->id,
                    'rejection_note' => $request->rejection_note,
                    'rejected_at' => now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Attendance correction rejected successfully'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this attendance correction'
            ], 403);

        } catch (\Exception $e) {
            Log::error('Reject attendance correction error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'correction_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject attendance correction'
            ], 500);
        }
    }
}

