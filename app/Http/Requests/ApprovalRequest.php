<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\{Leave, Overtime, ShiftChangeRequest, AttendanceCorrection};

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
            $model instanceof Overtime => $this->canApproveOvertime($user, $model),
            $model instanceof ShiftChangeRequest => $this->canApproveShiftChange($user, $model),
            $model instanceof AttendanceCorrection => $this->canApproveAttendanceCorrection($user, $model),
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

        if ($overtime = $this->route('overtime')) {
            return $overtime instanceof Overtime 
                ? $overtime 
                : Overtime::with('user')->find($overtime);
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

        return null;
    }

    private function canApproveLeave($user, Leave $leave): bool
    {
        // Must be the assigned supervisor
        return $leave->user && $leave->user->immediate_sup_id === $user->id;
    }

    private function canApproveOvertime($user, Overtime $overtime): bool
    {
        // Must be the supervisor OR project manager
        return $overtime->user && (
            $overtime->user->immediate_sup_id === $user->id ||
            $overtime->project_manager_id === $user->id
        );
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
