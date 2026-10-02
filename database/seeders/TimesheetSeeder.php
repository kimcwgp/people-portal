<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\TimeType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Eight weeks of weekly timesheets for the delivery crew: older weeks approved,
 * the most recent still a draft, one rejected so that path is visible too.
 */
class TimesheetSeeder extends Seeder
{
    private const WEEKS = 8;

    private const MEMOS = [
        'Sprint delivery and code review.',
        'Daily standup and sprint ceremonies.',
        'Timesheet, expenses and internal admin.',
        'Client sync and requirements walkthrough.',
        'Regression pass on the release candidate.',
        'Pairing on the migration script.',
    ];

    public function run(): void
    {
        mt_srand(20261001);

        $types = TimeType::ordered()->get();
        $projects = Project::orderBy('id')->get();

        if ($types->isEmpty() || $projects->isEmpty()) {
            $this->command->warn('Skipped timesheets -- run TimeTypeSeeder and ProjectSeeder first.');
            return;
        }

        $emails = [
            'employee1@peopleportal.test', 'employee2@peopleportal.test', 'teamlead@peopleportal.test',
            'faye.delacruz@peopleportal.test', 'gabriel.torres@peopleportal.test',
            'hannah.lim@peopleportal.test', 'ivan.bautista@peopleportal.test',
        ];

        $sheets = 0;
        $lines = 0;

        foreach (User::whereIn('email', $emails)->get() as $user) {
            $monday = Timesheet::weekStartFor(now());

            for ($i = 0; $i < self::WEEKS; $i++) {
                $weekStart = $monday->copy()->subWeeks($i);

                // This week is still a draft; one older week was sent back.
                $status = match (true) {
                    $i === 0 => 'draft',
                    $i === 3 => 'rejected',
                    $i <= 2 => 'pending',
                    default => 'approved',
                };

                $sheet = Timesheet::firstOrCreate(
                    ['user_id' => $user->id, 'week_start' => $weekStart->toDateString()],
                    [
                        'week_end' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                        'status' => $status,
                        'submitted_at' => $status === 'draft' ? null : $weekStart->copy()->addDays(7)->setTime(9, 0),
                        'approver_id' => in_array($status, ['approved', 'rejected'], true) ? $user->immediate_sup_id : null,
                        'approved_at' => $status === 'approved' ? $weekStart->copy()->addDays(8)->setTime(10, 0) : null,
                        'rejection_note' => $status === 'rejected' ? 'Friday looks short -- please re-check the hours against your standups.' : null,
                    ]
                );

                if (! $sheet->wasRecentlyCreated) {
                    continue;
                }

                $sheets++;
                $lines += $this->seedLines($sheet, $types, $projects, $status);
            }
        }

        $this->command->info("{$sheets} timesheets and {$lines} lines seeded.");
    }

    private function seedLines(Timesheet $sheet, $types, $projects, string $status): int
    {
        // A draft week is partly filled; everything else adds up to ~40 hours.
        $lineCount = mt_rand(3, 4);
        $created = 0;

        for ($n = 0; $n < $lineCount; $n++) {
            $type = $types[($sheet->id + $n) % $types->count()];
            $project = $projects[($sheet->id + $n) % $projects->count()];

            $hours = [];
            foreach (Timesheet::DAYS as $index => $day) {
                $isWeekend = $index >= 5;
                $filled = $status === 'draft' ? $index <= 2 : true;

                $hours["{$day}_hours"] = ($isWeekend || ! $filled)
                    ? 0
                    : round(mt_rand(10, 30) / 10, 2);
            }

            $sheet->entries()->create($hours + [
                'project_id' => $project->id,
                'project_ticket' => 'CW-' . (1000 + ($sheet->id * 10) + $n),
                'time_type_id' => $type->id,
                'memo' => self::MEMOS[($sheet->id + $n) % count(self::MEMOS)],
                'sort_order' => $n + 1,
            ]);

            $created++;
        }

        return $created;
    }
}
