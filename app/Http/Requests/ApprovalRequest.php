<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\{Leave, ShiftChangeRequest, AttendanceCorrection, Timesheet};

class ApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        
        // Super Admins can approve anything
        if ($user && $user->hasRole('Super Admin')) {
            return true;
        }

        // Determine which model we're dealing with based on route parameters
        $model = $this->getModelFromRoute();
        
        if (!$model) {
            return false;
        }

        // Check authorization based on model type
        return match(true) {
            $model instanceof Leave => $this->canApproveLeave($user, $model),
            $model instanceof ShiftChangeRequest => $this->canApproveShiftChange($user, $model),
            $model instanceof AttendanceCorrection => $this->canApproveAttendanceCorrection($user, $model),
            $model instanceof Timesheet => $this->canApproveTimesheet($user, $model),
            default => false
        };
    }

    private function getModelFromRoute()
    {
        // Try different route parameter names
        if ($leave = $this->route('leave')) {
            return $leave instanceof Leave 
                ? $leave 
                : Leave::with('user')->find($leave);
        }

        if ($shiftChangeRequest = $this->route('shiftChangeRequest')) {
            return $shiftChangeRequest instanceof ShiftChangeRequest 
                ? $shiftChangeRequest 
                : ShiftChangeRequest::with('user')->find($shiftChangeRequest);
        }

        if ($attendanceCorrection = $this->route('attendanceCorrection')) {
            return $attendanceCorrection instanceof AttendanceCorrection 
                ? $attendanceCorrection 
                : AttendanceCorrection::with('user')->find($attendanceCorrection);
        }

        if ($timesheet = $this->route('timesheet')) {
            return $timesheet instanceof Timesheet
                ? $timesheet
                : Timesheet::with('user')->find($timesheet);
        }

        return null;
    }

    private function canApproveLeave($user, Leave $leave): bool
    {
        // Must be the assigned supervisor
        return $leave->user && $leave->user->immediate_sup_id === $user->id;
    }

    private function canApproveShiftChange($user, ShiftChangeRequest $shiftRequest): bool
    {
        // Must be the assigned supervisor
        return $shiftRequest->user && $shiftRequest->user->immediate_sup_id === $user->id;
    }

    private function canApproveAttendanceCorrection($user, AttendanceCorrection $correction): bool
    {
        // Must be the assigned supervisor
        return $correction->user && $correction->user->immediate_sup_id === $user->id;
    }

    private function canApproveTimesheet($user, Timesheet $timesheet): bool
    {
        // Must be the assigned supervisor
        return $timesheet->user && $timesheet->user->immediate_sup_id === $user->id;
    }

    public function rules(): array
    {
        // If rejecting, require rejection note
        if ($this->isMethod('post') && str_contains($this->route()->getActionMethod(), 'reject')) {
            return [
                'rejection_note' => 'required|string|max:500',
            ];
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'rejection_note.required' => 'Please provide a reason for rejection.',
            'rejection_note.max' => 'Rejection note must not exceed 500 characters.',
        ];
    }
}
