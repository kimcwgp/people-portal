<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Standup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Daily standup entries for the delivery teams over the last two weeks of
 * weekdays, with one soft-deleted entry so the trashed filter has a row.
 */
class StandupSeeder extends Seeder
{
    /** Weekdays of standups to generate, counting back from today. */
    private const WEEKDAYS = 10;

    /** email => the projects that person actually works on. */
    private const ASSIGNMENTS = [
        'employee1@peopleportal.test'      => ['Northwind Fleet Tracker', 'Northwind Driver App'],
        'employee2@peopleportal.test'      => ['Bayanihan Loan Origination', 'Meridian Student Information'],
        'teamlead@peopleportal.test'       => ['Lumina Loyalty Platform', 'People Portal (Internal)'],
        'faye.delacruz@peopleportal.test'  => ['Cascade Patient Portal'],
        'gabriel.torres@peopleportal.test' => ['Solstice Ad Ops Console', 'Ironbark Site Inspections'],
        'hannah.lim@peopleportal.test'     => ['Cascade Patient Portal'],
        'enrico.santos@peopleportal.test'  => ['Solstice Ad Ops Console'],
        'ivan.bautista@peopleportal.test'  => ['Cascade QA Automation'],
        'divina.reyes@peopleportal.test'   => ['Cascade QA Automation', 'Pioneer Maintenance Desk'],
        'joyce.ramirez@peopleportal.test'  => ['Lumina Storefront Revamp'],
        'kevin.aguilar@peopleportal.test'  => ['Pioneer Maintenance Desk'],
        // The fixed accounts track the internal portal work.
        'superadmin@peopleportal.test'     => ['People Portal (Internal)'],
        'admin@peopleportal.test'          => ['People Portal (Internal)'],
        'hr@peopleportal.test'             => ['People Portal (Internal)'],
        'manager@peopleportal.test'        => ['People Portal (Internal)'],
    ];

    private const WORK = [
        'Finished the ticket from yesterday and opened a PR.',
        'Reviewed two pull requests and merged the approved one.',
        'Wrote unit tests for the service layer.',
        'Fixed the failing pipeline and re-ran the suite.',
        'Paired with QA on reproducing the reported defect.',
        'Refactored the report query -- response time is down to under a second.',
        'Drafted the API contract and shared it with the client.',
        'Handled support escalations and logged the root cause.',
        'Updated the design tokens and rebuilt the component library.',
        'Cleared the review backlog and updated the sprint board.',
    ];

    private const IMPEDIMENTS = [
        'None.',
        'None.',
        'None.',
        'Waiting on client feedback for the wireframes.',
        'Blocked by the staging environment being down.',
        'Need credentials for the sandbox API.',
        'Waiting for QA to confirm the fix.',
        'Dependency upgrade is breaking two tests -- investigating.',
    ];

    public function run(): void
    {
        mt_srand(20260910);

        $projects = Project::all()->keyBy('project_name');
        $created = 0;

        foreach (self::ASSIGNMENTS as $email => $projectNames) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                continue;
            }

            $date = Carbon::today();
            $remaining = self::WEEKDAYS;
            $index = 0;

            while ($remaining > 0) {
                if ($date->isWeekend()) {
                    $date = $date->copy()->subDay();
                    continue;
                }

                $project = $projects->get($projectNames[$index % count($projectNames)]);
                $index++;
                $remaining--;
                $standupDate = $date->copy();
                $date = $date->copy()->subDay();

                if (! $project) {
                    continue;
                }

                $exists = Standup::withTrashed()
                    ->where('user_id', $user->id)
                    ->where('project_id', $project->id)
                    ->whereDate('standup_date', $standupDate)
                    ->exists();

                if ($exists) {
                    continue;
                }

                Standup::create([
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'notes' => self::WORK[array_rand(self::WORK)],
                    'impediments' => self::IMPEDIMENTS[array_rand(self::IMPEDIMENTS)],
                    'standup_date' => $standupDate,
                    'time_spent_minutes' => mt_rand(4, 9) * 60,
                ]);

                $created++;
            }
        }

        $this->softDeleteOne();

        $this->command->info("{$created} standup entries seeded.");
    }

    /**
     * Leave one entry in the recycle bin so soft-delete filters have data.
     */
    private function softDeleteOne(): void
    {
        $standup = Standup::whereDate('standup_date', '<', Carbon::today())
            ->orderBy('id')
            ->first();

        if ($standup && ! $standup->trashed()) {
            $standup->delete();
        }
    }
}
