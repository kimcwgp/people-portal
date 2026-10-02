<?php

namespace Database\Seeders;

use App\Models\LeaveCredit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Grants this year's leave credits to every user and back-fills last year's
 * closed balances for anyone already hired then.
 *
 * Only the *grant* columns are written here (credits, carry-over, birthday
 * leave). Used and pending balances are left to LeaveObserver, which keeps
 * them in step with the leaves seeded by LeaveSeeder -- so this seeder must
 * run before that one.
 */
class LeaveCreditSeeder extends Seeder
{
    private const ANNUAL_VL = 15.00;
    private const ANNUAL_SL = 15.00;
    private const ANNUAL_PTO = 10.00;

    public function run(): void
    {
        $currentYear = Carbon::today()->year;
        $lastYear = $currentYear - 1;
        $seeded = 0;

        foreach (User::with('employee')->get() as $user) {
            $employee = $user->employee;
            $hireDate = $employee?->hire_date ? Carbon::parse($employee->hire_date) : null;
            $isRegular = $employee?->employment_status === 'Regular';

            // Probationary staff and interns accrue monthly instead of getting
            // the full annual grant up front.
            $monthsThisYear = $hireDate && $hireDate->year === $currentYear
                ? max(1, (int) $hireDate->diffInMonths(Carbon::today()))
                : 12;

            $vlGrant = $isRegular
                ? self::ANNUAL_VL
                : round(min($monthsThisYear, 12) * 0.5, 2);

            $slGrant = $isRegular
                ? self::ANNUAL_SL
                : round(min($monthsThisYear, 12) * 0.5, 2);

            // Unused VL from last year carries over, capped at 5 days. Only
            // some people end the year with a balance -- giving it to everyone
            // would mean every approved leave is charged to carry-over and
            // vl_used would never move.
            $carriedOver = $hireDate && $hireDate->year < $currentYear && $user->id % 3 === 0
                ? 5.00
                : 0.00;

            // Birthday leave needs a full year of tenure.
            $birthdayLeave = $hireDate && $hireDate->copy()->addYear()->lessThanOrEqualTo(Carbon::today()) ? 1.00 : 0.00;

            // PTO mirrors the VL rule. CTO is compensatory time off granted by
            // HR, so it is seeded deterministically rather than derived.
            $ptoGrant = $isRegular ? self::ANNUAL_PTO : round(min($monthsThisYear, 12) * 0.35, 2);
            $ctoHours = $isRegular ? round(($user->id % 5) * 2.5, 2) : 0.00;

            $this->grant($user, $currentYear, [
                'vl_credits' => $vlGrant,
                'sl_credits' => $slGrant,
                'vl_carried_over' => $carriedOver,
                'birthday_leave_count' => $birthdayLeave,
                'pto_credits' => $ptoGrant,
                'cto_hours' => $ctoHours,
            ]);
            $seeded++;

            // Last year's closed record, for anyone who was already on board.
            if ($hireDate && $hireDate->year < $currentYear) {
                $vlUsed = 10.00 - ($user->id % 4);
                $slUsed = 6.00 - ($user->id % 3);

                LeaveCredit::updateOrCreate(
                    ['user_id' => $user->id, 'year' => $lastYear],
                    [
                        'vl_credits' => self::ANNUAL_VL,
                        'vl_used' => $vlUsed,
                        'vl_pending' => 0,
                        'vl_carried_over' => 0,
                        'vl_carried_over_used' => 0,
                        'sl_credits' => self::ANNUAL_SL,
                        'sl_used' => $slUsed,
                        'sl_pending' => 0,
                        'birthday_leave_count' => 0,
                        'pto_credits' => self::ANNUAL_PTO,
                        'pto_used' => min(self::ANNUAL_PTO, 4.00 + ($user->id % 3)),
                        'pto_pending' => 0,
                        'cto_hours' => 0,
                        'cto_used_hours' => 0,
                        'cto_pending_hours' => 0,
                    ]
                );
                $seeded++;
            }
        }

        $this->command->info("{$seeded} leave credit records seeded ({$lastYear} and {$currentYear}).");
    }

    /**
     * Write the grant columns without clobbering used/pending balances that
     * the observer may already have calculated.
     */
    private function grant(User $user, int $year, array $grants): void
    {
        $credit = LeaveCredit::firstOrCreate(
            ['user_id' => $user->id, 'year' => $year],
            $grants
        );

        $credit->fill($grants)->save();
    }
}
