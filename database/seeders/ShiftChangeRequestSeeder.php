<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\ShiftChangeRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Shift change requests in all four states, including 'applied' rows whose
 * effective date has already passed (what ApplyShiftChanges leaves behind).
 */
class ShiftChangeRequestSeeder extends Seeder
{
    /**
     * [email, requested shift start time, days from today, reason, status]
     */
    private const REQUESTS = [
        ['employee1@peopleportal.test',      '09:00:00', -45, 'Moving further from the office; the later start avoids peak traffic.', 'applied'],
        ['joyce.ramirez@peopleportal.test',  '10:00:00', -30, 'Studio bookings only open after 10am.',                                'applied'],
        ['gabriel.torres@peopleportal.test', '20:00:00', -20, 'Joining the night crew to cover the Solstice account.',                'applied'],
        ['employee2@peopleportal.test',      '08:00:00', -12, 'Earlier start so I can pick up my child from school.',                 'approved'],
        ['ivan.bautista@peopleportal.test',  '10:00:00',  -6, 'Evening classes finish late; asking for a later start.',               'rejected'],
        ['hannah.lim@peopleportal.test',     '09:00:00',   4, 'Aligning with the rest of the squad for standups.',                    'pending'],
        ['faye.delacruz@peopleportal.test',  '08:00:00',   7, 'Gym schedule changed; earlier shift works better.',                    'pending'],
        ['kevin.aguilar@peopleportal.test',  '19:00:00',  10, 'Covering the later support window for US clients.',                    'pending'],
        ['liza.domingo@peopleportal.test',   '09:00:00',  14, 'Month-end close runs late; a later start helps.',                      'pending'],
        ['nadine.ocampo@peopleportal.test',  '10:00:00',  21, 'University schedule for the last term.',                               'pending'],
        ['bea.villanueva@peopleportal.test', '09:00:00',  28, 'Aligning with the HR service window.',                                 'pending'],
        ['enrico.santos@peopleportal.test',  '18:00:00',  30, 'Shifting an hour earlier to overlap more with the day team.',          'pending'],
    ];

    public function run(): void
    {
        $shifts = Shift::all()->keyBy(fn (Shift $shift) => $shift->getRawOriginal('start_time'));
        $hr = User::where('email', 'hr@peopleportal.test')->first();
        $today = Carbon::today();
        $created = 0;

        foreach (self::REQUESTS as [$email, $shiftStart, $offset, $reason, $status]) {
            $user = User::with('immediateSupervisor')->where('email', $email)->first();
            $requestedShift = $shifts->get($shiftStart);

            if (! $user || ! $requestedShift) {
                $this->command->warn("Skipped shift change for {$email} -- user or shift {$shiftStart} not found.");
                continue;
            }

            $effectiveDate = $today->copy()->addDays($offset);

            $exists = ShiftChangeRequest::where('user_id', $user->id)
                ->whereDate('effective_date', $effectiveDate)
                ->exists();

            if ($exists) {
                continue;
            }

            $approver = $user->immediateSupervisor ?? $hr;
            $decided = in_array($status, ['approved', 'rejected', 'applied'], true);

            ShiftChangeRequest::create([
                'user_id' => $user->id,
                'current_shift_id' => $user->shift_id,
                'requested_shift_id' => $requestedShift->id,
                'effective_date' => $effectiveDate,
                'reason' => $reason,
                'status' => $status,
                'approver_id' => $decided ? $approver?->id : null,
                'approver_notes' => match ($status) {
                    'approved', 'applied' => 'Approved -- coverage confirmed with the team.',
                    'rejected' => 'Cannot approve for now; the shift would leave the account uncovered.',
                    default => null,
                },
                'approved_at' => in_array($status, ['approved', 'applied'], true)
                    ? $effectiveDate->copy()->subDays(5)->setTime(14, 0)
                    : null,
            ]);

            $created++;
        }

        $this->command->info("{$created} shift change requests seeded.");
    }
}
