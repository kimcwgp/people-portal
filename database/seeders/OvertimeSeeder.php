<?php

namespace Database\Seeders;

use App\Models\Overtime;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Overtime filings in every status, tied to real projects so the project
 * manager column and the team/proxy overtime screens have something to join
 * against.
 */
class OvertimeSeeder extends Seeder
{
    /**
     * [email, project, days from today, time in, time out, notes, status]
     */
    private const OVERTIMES = [
        ['employee1@peopleportal.test',      'Northwind Fleet Tracker',      -28, '17:00:00', '20:00:00', 'Hotfix for the dispatcher map not refreshing.',        'APPROVED'],
        ['employee2@peopleportal.test',      'Bayanihan Loan Origination',   -24, '17:00:00', '21:00:00', 'Credit scoring migration had to finish before cutover.', 'APPROVED'],
        ['teamlead@peopleportal.test',       'Lumina Loyalty Platform',      -21, '17:30:00', '22:00:00', 'Release night: deployment and smoke tests.',           'APPROVED'],
        ['faye.delacruz@peopleportal.test',  'Cascade Patient Portal',       -17, '18:00:00', '21:30:00', 'Client demo preparation.',                             'APPROVED'],
        ['gabriel.torres@peopleportal.test', 'Solstice Ad Ops Console',      -14, '05:00:00', '08:00:00', 'Early start to cover the Tokyo handover.',             'APPROVED'],
        ['ivan.bautista@peopleportal.test',  'Cascade QA Automation',        -11, '17:00:00', '20:30:00', 'Regression run before the release candidate.',         'APPROVED'],
        ['enrico.santos@peopleportal.test',  'Solstice Ad Ops Console',       -9, '04:00:00', '07:00:00', 'Production incident: ad server queue backed up.',      'APPROVED'],
        ['hannah.lim@peopleportal.test',     'Cascade Patient Portal',        -7, '17:00:00', '19:30:00', 'Pair debugging the appointment timezone bug.',         'REJECTED'],
        ['kevin.aguilar@peopleportal.test',  'Pioneer Maintenance Desk',      -5, '18:00:00', '22:00:00', 'Backlog of support tickets after the outage.',         'CANCELLED'],
        ['joyce.ramirez@peopleportal.test',  'Lumina Storefront Revamp',      -3, '17:00:00', '20:00:00', 'Final asset export for the campaign launch.',          'APPROVED'],
        ['employee1@peopleportal.test',      'Northwind Driver App',          -2, '17:00:00', '21:00:00', 'Play Store build rejected, reworking permissions.',    'PENDING'],
        ['faye.delacruz@peopleportal.test',  'Cascade Patient Portal',        -1, '17:00:00', '20:00:00', 'Data migration dry run.',                              'PENDING'],
        ['employee2@peopleportal.test',      'Meridian Student Information',   1, '17:00:00', '21:00:00', 'Enrolment module cutover scheduled after hours.',      'PENDING'],
        ['gabriel.torres@peopleportal.test', 'Ironbark Site Inspections',      2, '17:00:00', '20:30:00', 'Offline sync fixes ahead of the site pilot.',          'PENDING'],
        ['divina.reyes@peopleportal.test',   'Cascade QA Automation',          3, '18:00:00', '22:00:00', 'Full regression sweep before UAT sign-off.',           'PENDING'],

        // Filings for the fixed TestUserSeeder accounts.
        ['superadmin@peopleportal.test',     'People Portal (Internal)',     -13, '17:00:00', '19:30:00', 'Board deck preparation.',                              'APPROVED'],
        ['manager@peopleportal.test',        'People Portal (Internal)',     -10, '17:00:00', '20:00:00', 'Release sign-off and sprint review prep.',             'APPROVED'],
        ['hr@peopleportal.test',             'People Portal (Internal)',      -6, '17:00:00', '21:00:00', 'Payroll cut-off processing.',                          'APPROVED'],
        ['admin@peopleportal.test',          'People Portal (Internal)',       5, '17:00:00', '20:00:00', 'Quarter-end reconciliation.',                          'PENDING'],
        ['teamlead@peopleportal.test',       'People Portal (Internal)',       6, '17:00:00', '21:00:00', 'Portal upgrade window.',                               'PENDING'],
    ];

    public function run(): void
    {
        $projects = Project::all()->keyBy('project_name');
        $hr = User::where('email', 'hr@peopleportal.test')->first();
        $today = Carbon::today();
        $created = 0;

        foreach (self::OVERTIMES as [$email, $projectName, $offset, $timeIn, $timeOut, $notes, $status]) {
            $user = User::with('immediateSupervisor')->where('email', $email)->first();
            $project = $projects->get($projectName);

            if (! $user || ! $project) {
                $this->command->warn("Skipped overtime for {$email} -- user or project '{$projectName}' not found.");
                continue;
            }

            $otDate = $today->copy()->addDays($offset);
            $approver = $user->immediateSupervisor ?? $hr;

            $exists = Overtime::withTrashed()
                ->where('user_id', $user->id)
                ->whereDate('ot_date', $otDate)
                ->exists();

            if ($exists) {
                continue;
            }

            Overtime::create([
                'project_id' => $project->id,
                'project_manager_id' => $project->pm_id,
                'user_id' => $user->id,
                'approver_id' => $approver?->id ?? $project->pm_id,
                'status' => $status,
                'ot_date' => $otDate,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'notes' => $notes,
                'rejection_note' => $status === 'REJECTED'
                    ? 'Work fits within regular hours -- please re-plan with your lead.'
                    : null,
                'cancellation_reason' => $status === 'CANCELLED'
                    ? 'Ticket backlog was cleared by the day shift, overtime no longer needed.'
                    : null,
                'cancelled_at' => $status === 'CANCELLED' ? $otDate->copy()->setTime(15, 45) : null,
                'cancelled_by' => $status === 'CANCELLED' ? $user->id : null,
            ]);

            $created++;
        }

        $this->command->info("{$created} overtime filings seeded.");
    }
}
