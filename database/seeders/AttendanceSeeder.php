<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Builds four weeks of weekday attendance for every active user, with lunch
 * and short breaks, a few missed clock-outs, open (still clocked in) records
 * for whoever is flagged online, and a set of attendance corrections in every
 * status.
 *
 * Records are keyed on (user_id, attendance_date), so re-running refreshes the
 * same rows instead of piling up duplicates.
 */
class AttendanceSeeder extends Seeder
{
    /** Weekdays of history to generate, counting back from today. */
    private const WEEKDAYS = 20;

    private const NOTES = [
        'Worked on the sprint backlog.',
        'Client call in the morning, focused work after lunch.',
        'Code review day.',
        'Release preparation.',
        'Documentation and backlog grooming.',
        null,
        null,
    ];

    public function run(): void
    {
        // Deterministic "randomness" so repeated runs produce the same data.
        mt_srand(20260909);

        $users = User::with(['shift', 'employee'])
            ->whereNotNull('shift_id')
            ->get()
            ->filter(fn (User $user) => $user->employee?->employee_status !== 'resigned');

        if ($users->isEmpty()) {
            $this->command->warn('No users with shifts found -- skipping attendance.');
            return;
        }

        $attendances = 0;
        $breaks = 0;

        foreach ($users as $user) {
            $date = Carbon::today();
            $remaining = self::WEEKDAYS;

            while ($remaining > 0) {
                if ($date->isWeekend()) {
                    $date = $date->copy()->subDay();
                    continue;
                }

                $result = $this->seedDay($user, $date->copy());
                $attendances += $result['attendance'];
                $breaks += $result['breaks'];

                $remaining--;
                $date = $date->copy()->subDay();
            }
        }

        $this->command->info("{$attendances} attendance records and {$breaks} breaks seeded.");

        $this->seedCorrections();
    }

    /**
     * @return array{attendance:int, breaks:int}
     */
    private function seedDay(User $user, Carbon $date): array
    {
        $isToday = $date->isToday();

        // Roughly one absence per user per month, but never today.
        if (! $isToday && mt_rand(1, 22) === 1) {
            return ['attendance' => 0, 'breaks' => 0];
        }

        $shiftStart = Carbon::parse($date->toDateString() . ' ' . $user->shift->getRawOriginal('start_time'));
        $shiftEnd = Carbon::parse($date->toDateString() . ' ' . $user->shift->getRawOriginal('end_time'));

        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay(); // Night shift crosses midnight.
        }

        // Most people clock in a few minutes early, some are late.
        $timeIn = $shiftStart->copy()->addMinutes(mt_rand(-10, 25));

        // Today's record stays open for whoever is flagged online.
        $stillClockedIn = $isToday && $user->online;

        // Occasionally someone forgets to clock out.
        $forgotClockOut = ! $isToday && mt_rand(1, 18) === 1;

        $timeOut = ($stillClockedIn || $forgotClockOut)
            ? null
            : $shiftEnd->copy()->addMinutes(mt_rand(-5, 45));

        // Never clock someone out in the future -- a shift that has not ended
        // yet (night shifts, mostly) stays open.
        if ($timeOut && $timeOut->greaterThan(now())) {
            $timeOut = null;
            $stillClockedIn = true;
        }

        // A partial day for anyone clocked in right now.
        if ($stillClockedIn && $timeIn->greaterThan(now())) {
            $timeIn = now()->copy()->subHours(2);
        }

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'attendance_date' => $date->toDateString()],
            [
                'shift_id' => $user->shift_id,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'notes' => self::NOTES[array_rand(self::NOTES)],
                'healthcheckJson' => [
                    'symptoms' => mt_rand(1, 12) === 1 ? ['headache'] : [],
                    'temperature' => round(36.1 + (mt_rand(0, 9) / 10), 1),
                    'fit_to_work' => true,
                    'declared_at' => $timeIn->toIso8601String(),
                ],
            ]
        );

        $breaks = $this->seedBreaks($attendance, $timeIn, $timeOut, $stillClockedIn);

        return ['attendance' => 1, 'breaks' => $breaks];
    }

    private function seedBreaks(Attendance $attendance, Carbon $timeIn, ?Carbon $timeOut, bool $stillClockedIn): int
    {
        $created = 0;

        $lunchStart = $timeIn->copy()->addHours(4)->addMinutes(mt_rand(0, 40));

        // Someone currently on lunch: an open break with no ended_at.
        $onLunchNow = $stillClockedIn && mt_rand(1, 3) === 1;

        if ($lunchStart->lessThan(now())) {
            $lunchEnd = $lunchStart->copy()->addMinutes(mt_rand(45, 70));

            // Still out if the break has not finished yet in real time.
            $onLunchNow = $onLunchNow || $lunchEnd->greaterThan(now());

            $lunch = AttendanceBreak::firstOrCreate(
                ['attendance_id' => $attendance->id, 'type' => AttendanceBreak::TYPE_LUNCH],
                [
                    'started_at' => $lunchStart,
                    'ended_at' => $onLunchNow ? null : $lunchEnd,
                    'notes' => $onLunchNow ? 'Out for lunch' : null,
                ]
            );

            $created += $lunch->wasRecentlyCreated ? 1 : 0;
        }

        // Short "be right back" break on about a third of the days.
        $brbStart = $timeIn->copy()->addHours(mt_rand(2, 6))->addMinutes(mt_rand(0, 50));

        $brbEnd = $brbStart->copy()->addMinutes(mt_rand(5, 20));

        if (mt_rand(1, 3) === 1 && $brbEnd->lessThan(now())) {
            $brb = AttendanceBreak::firstOrCreate(
                ['attendance_id' => $attendance->id, 'type' => AttendanceBreak::TYPE_BRB],
                [
                    'started_at' => $brbStart,
                    'ended_at' => $brbEnd,
                    'notes' => 'Quick errand',
                ]
            );

            $created += $brb->wasRecentlyCreated ? 1 : 0;
        }

        return $created;
    }

    /**
     * Corrections are filed against real attendance rows: the missed
     * clock-outs first, then a few ordinary time adjustments.
     */
    private function seedCorrections(): void
    {
        $reasons = [
            'Forgot to clock out before leaving the office.',
            'Browser crashed while clocking in; actual time in was earlier.',
            'Clocked in from the client site, portal was unreachable.',
            'System recorded the wrong lunch break window.',
            'Power interruption at home during the night shift.',
            'Attended an early client call before clocking in.',
            'Left for an approved errand and forgot to end the break.',
            'Time out recorded after the handover meeting ended.',
        ];

        $statuses = ['approved', 'approved', 'approved', 'pending', 'pending', 'pending', 'rejected', 'rejected'];

        $candidates = Attendance::with('user.immediateSupervisor')
            ->whereNotNull('time_in')
            ->whereNull('time_out')
            ->whereDate('attendance_date', '<', Carbon::today())
            ->orderBy('attendance_date', 'desc')
            ->limit(8)
            ->get();

        $filler = Attendance::with('user.immediateSupervisor')
            ->whereNotNull('time_out')
            ->whereDate('attendance_date', '<', Carbon::today())
            ->whereNotIn('id', $candidates->pluck('id'))
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id')
            ->limit(max(0, 12 - $candidates->count()))
            ->get();

        $created = 0;

        foreach ($candidates->concat($filler)->values() as $index => $attendance) {
            $status = $statuses[$index % count($statuses)];
            $approver = $attendance->user?->immediateSupervisor;
            $shiftEnd = $attendance->time_in->copy()->addHours(9);

            $correction = AttendanceCorrection::firstOrCreate(
                ['attendance_id' => $attendance->id, 'user_id' => $attendance->user_id],
                [
                    'corrected_time_in' => $attendance->time_in->format('H:i:s'),
                    'corrected_time_out' => $shiftEnd->format('H:i:s'),
                    'corrected_lunch_start' => $attendance->time_in->copy()->addHours(4)->format('H:i:s'),
                    'corrected_lunch_end' => $attendance->time_in->copy()->addHours(5)->format('H:i:s'),
                    'reason' => $reasons[$index % count($reasons)],
                    'status' => $status,
                    'approver_id' => $status === 'pending' ? null : $approver?->id,
                    'rejection_note' => $status === 'rejected' ? 'No supporting record of the stated time out.' : null,
                    'approved_at' => $status === 'approved' ? $attendance->attendance_date->copy()->addDay()->setTime(9, 30) : null,
                    'rejected_at' => $status === 'rejected' ? $attendance->attendance_date->copy()->addDay()->setTime(10, 15) : null,
                ]
            );

            $created += $correction->wasRecentlyCreated ? 1 : 0;
        }

        $this->command->info("{$created} attendance corrections seeded.");
    }
}
