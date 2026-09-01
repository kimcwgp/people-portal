<?php

namespace App\Services;

use App\Models\{User, Attendance, AttendanceBreak, Leave, Overtime, ForApproval, ShiftChangeRequest, LeaveCredit};
use App\Services\{BreakCalculationService, RingCentralService};
use Carbon\Carbon;
use Illuminate\Support\Facades\{Log, DB};

class AttendanceService
{
    private BreakCalculationService $breakService;
    private RingCentralService $ringCentral;

    public function __construct(
        BreakCalculationService $breakService,
        RingCentralService $ringCentral
    ) {
        $this->breakService = $breakService;
        $this->ringCentral = $ringCentral;
    }

    /**
     * Handle clock in/out toggle with notes support
     */
    public function toggleClock(User $user, ?string $notes = null): array
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $attendance = $this->getTodayAttendance($user, $today);

        if (!$attendance->time_in) {
            return $this->clockIn($user, $attendance, $now, $notes);
        }

        if (!$attendance->time_out) {
            return $this->clockOut($user, $attendance, $now, $notes);
        }

        return [
            'message' => 'Already clocked out for today',
            'status' => 'ALREADY_OUT',
            'attendance' => $this->formatAttendanceData($attendance, $user)
        ];
    }

    /**
     * Clock in user with notes
     */
    private function clockIn(User $user, Attendance $attendance, Carbon $now, ?string $notes = null): array
    {
        $attendance->time_in = $now;
        
        if (!empty($notes)) {
            $attendance->notes = $notes;
        }
        
        $attendance->save();
        $user->online = true;
        $user->save();

        // Send notification
        if (!empty($user->glip_url)) {
            $this->ringCentral->sendAttendanceNotification(
                $user, 'TIME IN', $now, $user->glip_url, $notes
            );
        }

        return [
            'message' => 'Successfully clocked in',
            'status' => 'TIME_IN',
            'time_in' => $now->format('H:i'),
            'attendance' => $this->formatAttendanceData($attendance->fresh('breaks'), $user)
        ];
    }

    /**
     * Clock out user with notes 
     */
    private function clockOut(User $user, Attendance $attendance, Carbon $now, ?string $notes = null): array
    {
        $attendance->breaks()
            ->whereNull('ended_at')
            ->update(['ended_at' => $now]);

        $attendance->time_out = $now;
        
        if (!empty($notes)) {
            $attendance->notes = $notes;
        }
        
        $attendance->save();

        $user->online = false;
        $user->save();

        // Send notification
        if (!empty($user->glip_url)) {
            $this->ringCentral->sendAttendanceNotification(
                $user, 'TIME OUT', $now, $user->glip_url, $notes
            );
        }

        return [
            'message' => 'Successfully clocked out',
            'status' => 'TIME_OUT',
            'time_out' => $now->format('H:i'),
            'attendance' => $this->formatAttendanceData($attendance->fresh('breaks'), $user)
        ];
    }

    /**
     * Start a break with notes support (NEW)
     */
    public function startBreak(User $user, string $type, ?string $notes = null): array
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $config = $this->breakService->getBreakConfig();

        $attendance = $this->getTodayAttendance($user, $today);

        // Validation
        if (!$attendance->time_in) {
            throw new \Exception('You need to time in first');
        }

        if ($attendance->time_out) {
            throw new \Exception('You are already timed out');
        }

        if ($this->breakService->getActiveBreak($attendance)) {
            throw new \Exception('Another break is already active');
        }

        // Create break record with notes
        $breakRecord = $attendance->breaks()->create([
            'type' => $type,
            'started_at' => $now,
            'notes' => $notes, // Add notes to break record
        ]);

        // Send notification
        $attendance->load('breaks');
        $remaining = null;
        
        // Only calculate remaining for configured break types
        if (isset($config[$type])) {
            $remaining = $this->breakService->getRemainingMinutesForType($attendance, $type, $now);
        }
        
        if (!empty($user->glip_url)) {
            $this->ringCentral->sendBreakNotification(
                $user, $type, 'START', $now, $user->glip_url, $remaining, null, $notes
            );
        }

        $label = $config[$type]['label'] ?? ucfirst($type);

        return [
            'message' => $label . ' started',
            'attendance' => $this->formatAttendanceData($attendance, $user)
        ];
    }

    /**
     * End a break with notes support (UPDATED)
     */
    public function endBreak(User $user, string $type, ?string $notes = null): array
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $config = $this->breakService->getBreakConfig();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance) {
            throw new \Exception('No attendance record found for today');
        }

        $activeBreak = $attendance->breaks()
            ->where('type', $type)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if (!$activeBreak) {
            $label = isset($config[$type]) ? strtolower($config[$type]['label']) : $type;
            throw new \Exception("No active {$label} break to end");
        }

        // Update break with end time and notes
        $endNotes = $notes;
        if ($activeBreak->notes && $notes) {
            $endNotes = $activeBreak->notes . ' | End: ' . $notes;
        } elseif ($activeBreak->notes && !$notes) {
            $endNotes = $activeBreak->notes;
        }

        $activeBreak->update([
            'ended_at' => $now,
            'notes' => $endNotes
        ]);

        $attendance->load('breaks');

        // Send notification
        $remaining = null;
        if (isset($config[$type])) {
            $remaining = $this->breakService->getRemainingMinutesForType($attendance, $type, $now);
        }
        
        if (!empty($user->glip_url)) {
            $this->ringCentral->sendBreakNotification(
                $user, $type, 'END', $now, $user->glip_url, $remaining, null, $endNotes
            );
        }

        $label = $config[$type]['label'] ?? ucfirst($type);

        return [
            'message' => $label . ' ended',
            'attendance' => $this->formatAttendanceData($attendance, $user)
        ];
    }

    /**
     * Format attendance data with enhanced break information
     */
    public function formatAttendanceData(Attendance $attendance, User $user): array
    {
        $now = Carbon::now();
        $config = $this->breakService->getBreakConfig();

        if (!$attendance->time_in) {
            return [
                'status' => 'OUT',
                'time_in' => null,
                'time_out' => null,
                'working_hours' => '0h 0m',
                'is_logged_in' => false,
                'break_time_remaining' => isset($config['lunch']) && $config['lunch']['allowance'] > 0
                    ? $this->breakService->formatMinutes((int) $config['lunch']['allowance'])
                    : '—',
                'active_break' => null,
                'break_allowances' => $this->breakService->getBreakAllowances(),
                'break_labels' => $this->getBreakLabels(),
                'daily_notes' => $attendance->notes ?? '',
            ];
        }

        $start = $attendance->time_in instanceof Carbon 
            ? $attendance->time_in 
            : Carbon::parse($attendance->time_in);

        $end = $attendance->time_out 
            ? ($attendance->time_out instanceof Carbon ? $attendance->time_out : Carbon::parse($attendance->time_out))
            : $now;

        $netWorkedMinutes = $this->breakService->calculateNetWorkingMinutes($attendance, $now);
        $activeBreak = $this->breakService->getActiveBreak($attendance);

        // Determine status
        $status = $attendance->time_out 
            ? 'OUT' 
            : ($activeBreak ? strtoupper($config[$activeBreak->type]['label'] ?? $activeBreak->type) : 'IN');

        // Calculate break time remaining
        if ($activeBreak) {
            $allowance = isset($config[$activeBreak->type]) ? (int) $config[$activeBreak->type]['allowance'] : 0;
            $remainingStr = $allowance > 0
                ? $this->breakService->formatMinutes($this->breakService->getRemainingMinutesForType($attendance, $activeBreak->type, $now) ?? 0)
                : '—';
        } else {
            $remainingStr = isset($config['lunch']) && $config['lunch']['allowance'] > 0
                ? $this->breakService->formatMinutes($this->breakService->getRemainingMinutesForType($attendance, 'lunch', $now) ?? (int) $config['lunch']['allowance'])
                : '—';
        }

        return [
            'status' => $status,
            'time_in' => $start->format('H:i'),
            'time_out' => $attendance->time_out ? $end->format('H:i') : '--:--',
            'working_hours' => $this->breakService->formatMinutes($netWorkedMinutes),
            'is_logged_in' => !$attendance->time_out,
            'break_time_remaining' => $remainingStr,
            'active_break' => $activeBreak ? [
                'type' => $activeBreak->type,
                'label' => $config[$activeBreak->type]['label'] ?? ucfirst($activeBreak->type),
                'started_at' => $activeBreak->started_at?->toIso8601String(),
                'allowance_minutes' => isset($config[$activeBreak->type]) ? (int) $config[$activeBreak->type]['allowance'] : 0,
                'deduct_from_work' => isset($config[$activeBreak->type]) ? (bool) $config[$activeBreak->type]['deduct'] : false,
                'notes' => $activeBreak->notes ?? '', // Include break notes
            ] : null,
            'break_allowances' => $this->breakService->getBreakAllowances(),
            'break_labels' => $this->getBreakLabels(),
            'daily_notes' => $attendance->notes ?? '',
        ];
    }

    // ===== NEW APPROVAL SYSTEM METHODS =====

    /**
     * Get dashboard data including approval requests - UPDATED
     */
    public function getDashboardData(User $user): array
    {
        $today = Carbon::today();
        $attendance = $this->getTodayAttendance($user, $today);

        return [
            'attendance' => $this->formatAttendanceData($attendance, $user),
            'requests' => $this->getApprovalRequests($user), 
            'stats' => $this->getUserStats($user),
            'announcements' => $this->getAnnouncements(),
        ];
    }

    /**
     * Get approval requests that need this user's approval - FIXED
     */
    public function getApprovalRequests(User $user)
    {
        try {
            // Load direct subordinates if not already loaded
            if (!$user->relationLoaded('directSubordinates')) {
                $user->load('directSubordinates');
            }

            // Get subordinate IDs
            $subordinateIds = $user->directSubordinates->pluck('id');
            
            if ($subordinateIds->isEmpty()) {
                return []; // No subordinates = no approval requests
            }

            // Get leave requests for subordinates
            $leaveRequests = Leave::with(['user', 'leaveType'])
                ->whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->latest()
                ->get()
                ->map(function ($leave) {
                    $userName = $leave->user->name ?? 'Unknown User';
                    $userEmail = $leave->user->email ?? '';
                    
                    return [
                        'id' => $leave->id,
                        'type' => 'leave',
                        'status' => $leave->status,
                        'created_at' => $leave->created_at,
                        'user_id' => $leave->user_id,
                        'name' => $userName,
                        'user_name' => $userName,
                        'email' => $userEmail,
                        'avatar' => $this->generateAvatar($userName),
                        'user_avatar' => $this->generateAvatar($userName),
                        'leave_type' => $leave->leaveType->name ?? 'N/A',
                        'start_date' => $leave->start_date ? Carbon::parse($leave->start_date)->format('M d, Y') : 'N/A',
                        'end_date' => $leave->end_date ? Carbon::parse($leave->end_date)->format('M d, Y') : 'N/A',
                        'duration' => $leave->calculated_days ? $leave->calculated_days . ' day(s)' : 'N/A',
                        'reason' => $leave->reason ?? 'No reason provided',
                    ];
                });

            // Get overtime requests for subordinates
            $overtimeRequests = Overtime::with(['user', 'project'])
                ->whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->latest()
                ->get()
                ->map(function ($overtime) {
                    $userName = $overtime->user->name ?? 'Unknown User';
                    $userEmail = $overtime->user->email ?? '';

                    return [
                        'id' => $overtime->id,
                        'type' => 'overtime',
                        'status' => $overtime->status,
                        'created_at' => $overtime->created_at,
                        'user_id' => $overtime->user_id,
                        'name' => $userName,
                        'user_name' => $userName,
                        'email' => $userEmail,
                        'avatar' => $this->generateAvatar($userName),
                        'user_avatar' => $this->generateAvatar($userName),
                        'ot_date' => $overtime->ot_date ? Carbon::parse($overtime->ot_date)->format('M d, Y') : 'N/A',
                        'time_in' => $overtime->time_in ? $overtime->time_in->format('H:i') : 'N/A',
                        'time_out' => $overtime->time_out ? $overtime->time_out->format('H:i') : 'N/A',
                        'project_name' => $overtime->project->project_name ?? 'No Project',
                        'ot_hours' => $overtime->ot_hours ?? 0,
                    ];
                });

            // Get shift change requests for subordinates
            $shiftRequests = ShiftChangeRequest::with(['user', 'currentShift', 'requestedShift'])
                ->whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->latest()
                ->get()
                ->map(function ($shiftRequest) {
                    $userName = $shiftRequest->user->name ?? 'Unknown User';
                    $userEmail = $shiftRequest->user->email ?? '';

                    return [
                        'id' => $shiftRequest->id,
                        'type' => 'shift',
                        'status' => $shiftRequest->status,
                        'created_at' => $shiftRequest->created_at,
                        'user_id' => $shiftRequest->user_id,
                        'name' => $userName,
                        'user_name' => $userName,
                        'email' => $userEmail,
                        'avatar' => $this->generateAvatar($userName),
                        'user_avatar' => $this->generateAvatar($userName),
                        'leave_type' => 'Shift Change Request',
                        'current_shift' => $shiftRequest->currentShift ?
                            ($shiftRequest->currentShift->shift_label ?? $shiftRequest->currentShift->shift_type) : 'No Shift',
                        'requested_shift' => $shiftRequest->requestedShift ?
                            ($shiftRequest->requestedShift->shift_label ?? $shiftRequest->requestedShift->shift_type) : 'Unknown',
                        'start_date' => $shiftRequest->effective_date ? Carbon::parse($shiftRequest->effective_date)->format('M d, Y') : 'N/A',
                        'effective_date' => $shiftRequest->effective_date ? Carbon::parse($shiftRequest->effective_date)->format('M d, Y') : 'N/A',
                        'reason' => $shiftRequest->reason ?? 'No reason provided',
                        'duration' => 'Effective ' . ($shiftRequest->effective_date ? Carbon::parse($shiftRequest->effective_date)->format('M d') : 'TBD'),
                    ];
                });

            // Convert to arrays before merging to avoid Collection issues
            $allRequests = array_merge(
                $leaveRequests->toArray(),
                $overtimeRequests->toArray(),
                $shiftRequests->toArray()
            );

            // Sort by created_at descending
            usort($allRequests, function ($a, $b) {
                return $b['created_at'] <=> $a['created_at'];
            });

            return $allRequests;

        } catch (\Exception $e) {
            Log::error('Error getting approval requests: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Generate avatar URL for user - NEW
     */
    protected function generateAvatar($name)
    {
        return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=random&color=fff&size=128";
    }

    /**
     * Format approval duration for general requests - NEW
     */
    protected function formatApprovalDuration($approval)
    {
        if (!$approval || !$approval->type_of_approval) {
            return 'Request';
        }
        
        switch ($approval->type_of_approval) {
            case 'attendance':
                return 'Attendance correction';
            case 'shift':
                return 'Shift change';
            default:
                return 'Request';
        }
    }

    /**
     * Get announcements - NEW (placeholder implementation)
     */
    protected function getAnnouncements()
    {
        try {
            return [];
        } catch (\Exception $e) {
            Log::error('Error getting announcements: ' . $e->getMessage());
            return [];
        }
    }

    public function getTodayAttendance(User $user, Carbon $date): Attendance
    {
        return Attendance::firstOrCreate(
            [
                'user_id' => $user->id, 
                'attendance_date' => $date
            ],
            ['shift_id' => 1]
        );
    }

    public function isUserClockedIn(User $user): bool
    {
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        return $attendance && $attendance->time_in && !$attendance->time_out;
    }

    public function hasActiveBreak(User $user, string $type): bool
    {
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance) {
            return false;
        }

        return $attendance->breaks()
            ->where('type', $type)
            ->whereNull('ended_at')
            ->exists();
    }

    public function shouldAutoTimeout(User $user): bool
    {
        if (!$this->isUserClockedIn($user)) {
            return false;
        }

        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance || !$attendance->time_in) {
            return false;
        }

        $shift = DB::table('shifts')->where('id', $user->shift_id)->first();
        
        if (!$shift || $shift->shift_type !== 'day') {
            return false;
        }

        $timeIn = $attendance->time_in instanceof Carbon 
            ? $attendance->time_in 
            : Carbon::parse($attendance->time_in);
        
        $now = Carbon::now();
        $currentTime = $now->format('H:i');
        $maxWorkHours = 12;
        $cutoffTime = '22:00'; 

        return ($currentTime > $cutoffTime) || ($now->diffInHours($timeIn) >= $maxWorkHours);
    }

    public function performAutoTimeout(User $user): array
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $attendance = $this->getTodayAttendance($user, $today);

        $attendance->breaks()
            ->whereNull('ended_at')
            ->update(['ended_at' => $now]);

        $attendance->time_out = $now;
        $currentNotes = $attendance->notes ?? '';
        $autoNote = 'Auto timed out at ' . $now->format('h:i A');
        $attendance->notes = $currentNotes ? $currentNotes . ' | ' . $autoNote : $autoNote;
        $attendance->save();

        $user->online = false;
        $user->save();

        return [
            'message' => 'Automatically timed out',
            'status' => 'AUTO_TIME_OUT',
            'attendance' => $this->formatAttendanceData($attendance->fresh('breaks'), $user)
        ];
    }

    public function getCurrentStatus(User $user): array
    {
        $today = Carbon::today();
        $attendance = $this->getTodayAttendance($user, $today);
        
        return $this->formatAttendanceData($attendance, $user);
    }

    private function getUserStats(User $user): array
    {
        try {
            $currentYear = now()->year;
            
            // Get leave credits from the leave_credits table
            $leaveCredit = LeaveCredit::where('user_id', $user->id)
                ->where('year', $currentYear)
                ->first();
            
            if ($leaveCredit) {
                return [
                    'vl_remaining' => max(0, $leaveCredit->vl_remaining),
                    'sl_remaining' => max(0, $leaveCredit->sl_remaining),
                    'vl_used' => $leaveCredit->vl_used,
                    'sl_used' => $leaveCredit->sl_used,
                    'vl_credits' => $leaveCredit->vl_credits,
                    'sl_credits' => $leaveCredit->sl_credits,
                    'vl_carried_over' => $leaveCredit->vl_carried_over ?? 0,
                    'vl_carried_over_used' => $leaveCredit->vl_carried_over_used ?? 0,
                    'vl_carried_over_remaining' => $leaveCredit->vl_carried_over_remaining ?? 0,
                    'birthday_leave_available' => $leaveCredit->birthday_leave_available ?? 0,
                ];
            }
            
            // Fallback to default values if no leave credits record exists
            return [
                'vl_remaining' => 0,
                'sl_remaining' => 0,
                'vl_used' => 0,
                'sl_used' => 0,
                'vl_credits' => 0,
                'sl_credits' => 0,
                'vl_carried_over' => 0,
                'vl_carried_over_used' => 0,
                'vl_carried_over_remaining' => 0,
                'birthday_leave_available' => 0,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting user stats: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'vl_remaining' => 0,
                'sl_remaining' => 0,
                'vl_used' => 0,
                'sl_used' => 0,
                'vl_credits' => 0,
                'sl_credits' => 0,
                'vl_carried_over' => 0,
                'vl_carried_over_used' => 0,
                'vl_carried_over_remaining' => 0,
                'birthday_leave_available' => 0,
            ];
        }
    }

    private function getBreakLabels(): array
    {
        $config = $this->breakService->getBreakConfig();
        $labels = [];
        
        foreach ($config as $type => $typeConfig) {
            $labels[$type] = $typeConfig['label'] ?? ucfirst($type);
        }
        
        return $labels;
    }

    public function sendLeaveApprovalNotification($leave, $action, $approver, $glipUrl, $rejectionNote = null)
    {
        try {
            $this->ringCentral->sendLeaveApprovalNotification(
                $leave, $action, $approver, $glipUrl, $rejectionNote
            );
        } catch (\Exception $e) {
            Log::error('Failed to send leave approval notification: ' . $e->getMessage());
        }
    }

    public function sendOvertimeApprovalNotification($overtime, $action, $approver, $glipUrl, $rejectionNote = null)
    {
        try {
            $this->ringCentral->sendOvertimeApprovalNotification(
                $overtime, $action, $approver, $glipUrl, $rejectionNote
            );
        } catch (\Exception $e) {
            Log::error('Failed to send overtime approval notification: ' . $e->getMessage());
        }
    }
}