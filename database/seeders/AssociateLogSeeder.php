<?php

namespace Database\Seeders;

use App\Models\AssociateLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * HR's running log of notable events per associate: coaching notes,
 * commendations, incident records and clearance items.
 */
class AssociateLogSeeder extends Seeder
{
    /**
     * [email, days from today, entry, attachment name or null]
     */
    private const LOGS = [
        ['employee1@peopleportal.test',      -140, 'Coaching session on sprint estimation accuracy. Agreed to break tickets down to two days or less.', null],
        ['employee1@peopleportal.test',       -62, 'Commendation from Northwind Logistics for the fleet dashboard turnaround.', 'client-commendation-northwind.pdf'],
        ['employee2@peopleportal.test',      -118, 'Attendance discussion: three late clock-ins in one cutover. Verbal reminder issued.', null],
        ['employee2@peopleportal.test',       -40, 'Completed the internal secure coding certification.', 'certificate-secure-coding.pdf'],
        ['teamlead@peopleportal.test',        -95, 'Promoted to Team Lead. Handover of individual contributor tickets documented.', 'promotion-memo.pdf'],
        ['gabriel.torres@peopleportal.test',  -80, 'Requested transfer from Quality Assurance to Development. Approved by both leads.', 'transfer-request.pdf'],
        ['hannah.lim@peopleportal.test',      -55, 'Six-month probation review: exceeded expectations on delivery, regularization endorsed.', 'probation-review.pdf'],
        ['ivan.bautista@peopleportal.test',   -47, 'Incident report: staging data was refreshed without notice. No production impact. Process updated.', 'incident-report-2026-07.pdf'],
        ['joyce.ramirez@peopleportal.test',   -35, 'Approved shift to part-time arrangement effective the following cutoff.', 'part-time-agreement.pdf'],
        ['kevin.aguilar@peopleportal.test',   -28, 'Client Support recognition: highest CSAT for the quarter.', null],
        ['miguel.pascual@peopleportal.test',  -71, 'Submitted resignation letter, last day set at the end of June.', 'resignation-letter.pdf'],
        ['miguel.pascual@peopleportal.test',  -60, 'Exit interview completed. Clearance signed by Finance, IT and HR.', 'clearance-form.pdf'],
        ['nadine.ocampo@peopleportal.test',   -18, 'Internship midpoint check-in. On track with the onboarding plan.', null],
        ['faye.delacruz@peopleportal.test',    -9, 'Nominated for the quarterly engineering excellence award.', null],
    ];

    public function run(): void
    {
        $author = User::where('email', 'hr@peopleportal.test')->first()
            ?? User::where('email', 'admin@peopleportal.test')->first();

        $today = Carbon::today();
        $created = 0;

        foreach (self::LOGS as $index => [$email, $offset, $entry, $attachment]) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                continue;
            }

            $date = $today->copy()->addDays($offset)->setTime(11, 0);

            $exists = AssociateLog::withTrashed()
                ->where('user_id', $user->id)
                ->whereDate('date', $date)
                ->exists();

            if ($exists) {
                continue;
            }

            AssociateLog::create([
                'user_id' => $user->id,
                'date' => $date,
                'entry_details' => $entry,
                'attachment_id' => $attachment ? 'sample-attachment-' . ($index + 1) : null,
                'attachment_name' => $attachment,
                'created_by' => $author?->id,
            ]);

            $created++;
        }

        $this->command->info("{$created} associate logs seeded.");
    }
}
