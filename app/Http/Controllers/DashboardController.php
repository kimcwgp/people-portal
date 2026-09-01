<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{Auth, Log, DB};
use App\Http\Requests\{ClockActionRequest, BreakActionRequest, ApprovalRequest};
use App\Http\Resources\{DashboardResource, TimeInOutResource};
use App\Services\{AttendanceService, RingCentralService};
use App\Models\{Leave, Overtime, ShiftChangeRequest, AttendanceCorrection, User};

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

                if (!empty($leave->user->glip_url)) {
                    $this->attendanceService->sendLeaveApprovalNotification(
                        $leave, 'approved', $user, $leave->user->glip_url
                    );
                }
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

                if (!empty($leave->user->glip_url)) {
                    $this->attendanceService->sendLeaveApprovalNotification(
                        $leave, 'rejected', $user, $leave->user->glip_url, $request->rejection_note
                    );
                }
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

    public function approveOvertime(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->user();
            $overtime = Overtime::with('user')->findOrFail($id);

            DB::transaction(function () use ($overtime, $user) {
                $overtime->update([
                    'status' => 'approved',
                    'approver_id' => $user->id,
                    'approved_at' => now(),
                ]);

                if (!empty($overtime->user->glip_url)) {
                    $this->attendanceService->sendOvertimeApprovalNotification(
                        $overtime, 'approved', $user, $overtime->user->glip_url
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Overtime request approved successfully'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to approve this overtime request'
            ], 403);

        } catch (\Exception $e) {
            Log::error('Approve overtime error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'overtime_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve overtime request'
            ], 500);
        }
    }

    public function rejectOvertime(ApprovalRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->user();
            $overtime = Overtime::with('user')->findOrFail($id);

            DB::transaction(function () use ($overtime, $user, $request) {
                $overtime->update([
                    'status' => 'rejected',
                    'approver_id' => $user->id,
                    'rejection_note' => $request->rejection_note,
                    'rejected_at' => now(),
                ]);

                if (!empty($overtime->user->glip_url)) {
                    $this->attendanceService->sendOvertimeApprovalNotification(
                        $overtime, 'rejected', $user, $overtime->user->glip_url, $request->rejection_note
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Overtime request rejected successfully'
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to reject this overtime request'
            ], 403);

        } catch (\Exception $e) {
            Log::error('Reject overtime error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'overtime_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject overtime request'
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

            $employee = $correction->user;
            if ($employee && $employee->glip_url) {
                $ringCentral = app(RingCentralService::class);
                $ringCentral->sendAttendanceCorrectionApprovalNotification(
                    $correction,
                    'approved',
                    $user,
                    $employee->glip_url
                );
            }

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

            $employee = $correction->user;
            if ($employee && $employee->glip_url) {
                $ringCentral = app(RingCentralService::class);
                $ringCentral->sendAttendanceCorrectionApprovalNotification(
                    $correction,
                    'rejected',
                    $user,
                    $employee->glip_url,
                    $request->rejection_note
                );
            }

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

