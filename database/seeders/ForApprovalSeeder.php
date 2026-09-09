<?php

namespace Database\Seeders;

use App\Models\ForApproval;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Mirrors a slice of the seeded leaves and overtimes into for_approvals.
 *
 * Nothing writes to this table at runtime any more -- Leave and Overtime carry
 * their own approval columns -- but it is still mapped on User, so it gets a
 * representative set of rows rather than being left empty.
 */
class ForApprovalSeeder extends Seeder
{
    public function run(): void
    {
        $hr = User::where('email', 'hr@peopleportal.test')->first();
        $created = 0;

        // Two of each status, so the mirrored rows are not all pending.
        $leaves = collect(['approved', 'pending', 'rejected', 'cancelled'])
            ->flatMap(fn (string $status) => Leave::with(['user.immediateSupervisor', 'leaveType', 'approver'])
                ->where('status', $status)
                ->orderBy('start_date', 'desc')
                ->limit(2)
                ->get());

        foreach ($leaves as $leave) {
            $approver = $leave->approver ?? $leave->user?->immediateSupervisor ?? $hr;

            if (! $leave->user || ! $approver) {
                continue;
            }

            $created += $this->record([
                'user_id' => $leave->user_id,
                'approver_id' => $approver->id,
                'approved_by' => $approver->name,
                'type_of_approval' => 'leaves',
                'details' => sprintf(
                    '%s from %s to %s (%s)',
                    $leave->leaveType?->name ?? 'Leave',
                    $leave->start_date->toDateString(),
                    $leave->end_date->toDateString(),
                    $leave->duration
                ),
                'other_details' => $leave->reason,
                'status' => $this->mapLeaveStatus($leave->status),
                'time_in' => $leave->start_date->copy()->setTime(8, 0),
                'time_out' => $leave->end_date->copy()->setTime(17, 0),
                'approved_at' => $leave->approved_at,
            ]);
        }

        $overtimes = collect(['APPROVED', 'PENDING', 'REJECTED', 'CANCELLED'])
            ->flatMap(fn (string $status) => Overtime::with(['user', 'project'])
                ->where('status', $status)
                ->orderBy('ot_date', 'desc')
                ->limit(2)
                ->get());

        foreach ($overtimes as $overtime) {
            $approver = User::find($overtime->approver_id) ?? $hr;

            if (! $overtime->user || ! $approver) {
                continue;
            }

            $created += $this->record([
                'user_id' => $overtime->user_id,
                'approver_id' => $approver->id,
                'approved_by' => $approver->name,
                'type_of_approval' => 'overtime',
                'details' => sprintf(
                    'Overtime on %s for %s',
                    $overtime->ot_date->toDateString(),
                    $overtime->project?->project_name ?? 'an unassigned project'
                ),
                'other_details' => $overtime->notes,
                'status' => $overtime->status === 'CANCELLED' ? 'REJECTED' : $overtime->status,
                'time_in' => Carbon::parse($overtime->ot_date->toDateString() . ' ' . $overtime->getRawOriginal('time_in')),
                'time_out' => Carbon::parse($overtime->ot_date->toDateString() . ' ' . $overtime->getRawOriginal('time_out')),
                'approved_at' => $overtime->status === 'APPROVED' ? $overtime->ot_date->copy()->setTime(16, 0) : null,
            ]);
        }

        $this->command->info("{$created} for_approvals records seeded.");
    }

    private function record(array $attributes): int
    {
        $approval = ForApproval::firstOrCreate(
            [
                'user_id' => $attributes['user_id'],
                'type_of_approval' => $attributes['type_of_approval'],
                'details' => $attributes['details'],
            ],
            $attributes
        );

        return $approval->wasRecentlyCreated ? 1 : 0;
    }

    private function mapLeaveStatus(string $status): string
    {
        return match ($status) {
            'approved' => 'APPROVED',
            'rejected', 'cancelled' => 'REJECTED',
            default => 'PENDING',
        };
    }
}
