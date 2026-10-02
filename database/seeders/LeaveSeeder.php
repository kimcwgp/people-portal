<?php

namespace Database\Seeders;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds leave filings across every status, duration and leave type.
 *
 * Each leave is created as pending and then moved to its final status, so
 * LeaveObserver does the credit bookkeeping exactly as it would in the app --
 * pending balances, used days and carry-over consumption all end up
 * consistent with the seeded leave_credits rows.
 */
class LeaveSeeder extends Seeder
{
    private const VL = 'Vacation Leave';
    private const SL_MC = 'Sick Leave (WITH Medical Certificate)';
    private const SL_NO_MC = 'Sick Leave (WITHOUT Medical Certificate)';
    private const EL = 'Emergency Leave';
    private const BEREAVEMENT = 'Bereavement Leave';
    private const MATERNITY = 'Maternity Leave';
    private const PATERNITY = 'Paternity Leave';
    private const BIRTHDAY = 'Birthday Leave (only applicable to associates with at least 1 year tenure)';
    private const LWOP = 'VLEL - Leave without Pay (for interns/probee/part-timers)';
    private const OTHER = 'Other';

    /**
     * [email, leave type, start, end, duration, reason, final status]
     */
    private const LEAVES = [
        // Approved history. The fixed TestUserSeeder accounts get their own
        // filings too, so 'My Leaves' is populated whoever you log in as.
        ['superadmin@peopleportal.test',     self::VL,          '-140 days', '-138 days', 'All Day',                 'Annual family holiday.',                              'approved'],
        ['hr@peopleportal.test',             self::VL,          '-88 days',  '-87 days',  'All Day',                 'Long weekend out of town.',                           'approved'],
        ['manager@peopleportal.test',        self::EL,          '-41 days',  '-41 days',  'All Day',                 'Water outage at home.',                               'approved'],
        ['admin@peopleportal.test',          self::SL_MC,       '-26 days',  '-25 days',  'All Day',                 'Flu, medical certificate submitted.',                 'approved'],
        ['employee1@peopleportal.test',      self::VL,          '-120 days', '-118 days', 'All Day',                 'Family trip to Baguio.',                              'approved'],
        ['employee2@peopleportal.test',      self::SL_MC,       '-96 days',  '-95 days',  'All Day',                 'Flu with medical certificate attached.',              'approved'],
        ['teamlead@peopleportal.test',       self::VL,          '-75 days',  '-71 days',  'All Day',                 'Annual leave -- out of the country.',                 'approved'],
        ['faye.delacruz@peopleportal.test',  self::EL,          '-60 days',  '-60 days',  'All Day',                 'Burst pipe at home, had to stay for repairs.',        'approved'],
        ['gabriel.torres@peopleportal.test', self::SL_NO_MC,    '-45 days',  '-45 days',  'Half Day (1pm to 5pm)',   'Migraine, went home after lunch.',                    'approved'],
        ['hannah.lim@peopleportal.test',     self::VL,          '-38 days',  '-37 days',  'All Day',                 'Cousin\'s wedding out of town.',                      'approved'],
        ['ivan.bautista@peopleportal.test',  self::SL_MC,       '-30 days',  '-28 days',  'All Day',                 'Dengue, cleared by the company doctor.',              'approved'],
        ['joyce.ramirez@peopleportal.test',  self::BEREAVEMENT, '-24 days',  '-22 days',  'All Day',                 'Passing of a grandparent.',                           'approved'],
        ['bea.villanueva@peopleportal.test', self::BIRTHDAY,    '-18 days',  '-18 days',  'All Day',                 'Birthday leave.',                                     'approved'],
        ['kevin.aguilar@peopleportal.test',  self::VL,          '-14 days',  '-13 days',  'All Day',                 'Long weekend with family.',                           'approved'],
        ['enrico.santos@peopleportal.test',  self::PATERNITY,   '-10 days',  '-4 days',   'All Day',                 'Paternity leave for our second child.',               'approved'],

        // Rejected.
        ['employee1@peopleportal.test',      self::VL,          '-52 days',  '-50 days',  'All Day',                 'Concert trip with friends.',                          'rejected'],
        ['gabriel.torres@peopleportal.test', self::VL,          '-20 days',  '-16 days',  'All Day',                 'Extended holiday abroad.',                            'rejected'],
        ['nadine.ocampo@peopleportal.test',  self::LWOP,        '-8 days',   '-8 days',   'All Day',                 'Personal errand.',                                    'rejected'],

        // Cancelled -- one straight from pending, two that were approved first
        // so the credit reversal path gets exercised too.
        ['employee2@peopleportal.test',      self::VL,          '-33 days',  '-32 days',  'All Day',                 'Trip that ended up being called off.',                'cancelled'],
        ['divina.reyes@peopleportal.test',   self::EL,          '-12 days',  '-12 days',  'All Day',                 'Household emergency that resolved itself.',           'cancelled_after_approval'],
        ['liza.domingo@peopleportal.test',   self::SL_NO_MC,    '-6 days',   '-6 days',   'Half Day (8am to 12nn)',  'Felt unwell but recovered before the shift.',         'cancelled_after_approval'],

        // Pending -- these are what approvers will see in their queue.
        ['employee1@peopleportal.test',      self::VL,          '+5 days',   '+7 days',   'All Day',                 'Pre-planned family vacation.',                        'pending'],
        ['employee2@peopleportal.test',      self::VL,          '+12 days',  '+12 days',  'Half Day (8am to 12nn)',  'Passport appointment in the morning.',                'pending'],
        ['faye.delacruz@peopleportal.test',  self::SL_NO_MC,    '-1 days',   '-1 days',   'All Day',                 'Food poisoning, filed the next working day.',         'pending'],
        ['hannah.lim@peopleportal.test',     self::BIRTHDAY,    '+21 days',  '+21 days',  'All Day',                 'Birthday leave.',                                     'pending'],
        ['ivan.bautista@peopleportal.test',  self::VL,          '+18 days',  '+20 days',  'All Day',                 'Semestral break with the kids.',                      'pending'],
        ['kevin.aguilar@peopleportal.test',  self::EL,          '+2 days',   '+2 days',   'Custom',                  'Barangay clearance appointment.',                     'pending'],
        ['joyce.ramirez@peopleportal.test',  self::OTHER,       '+9 days',   '+9 days',   'All Day',                 'Jury duty equivalent -- court summons.',              'pending'],
        ['admin@peopleportal.test',          self::MATERNITY,   '+30 days',  '+90 days',  'All Day',                 'Maternity leave, expected delivery next month.',      'pending'],
        ['nadine.ocampo@peopleportal.test',  self::LWOP,        '+4 days',   '+4 days',   'All Day',                 'Final university requirement defence.',               'pending'],
        ['superadmin@peopleportal.test',     self::VL,          '+24 days',  '+26 days',  'All Day',                 'Offsite planning week, working from the province.',   'pending'],
        ['hr@peopleportal.test',             self::BIRTHDAY,    '+15 days',  '+15 days',  'All Day',                 'Birthday leave.',                                     'pending'],
        ['manager@peopleportal.test',        self::VL,          '+27 days',  '+28 days',  'All Day',                 'School break with the kids.',                         'pending'],
        ['teamlead@peopleportal.test',       self::SL_NO_MC,    '-2 days',   '-2 days',   'Half Day (1pm to 5pm)',   'Bad headache, logged off after lunch.',               'pending'],
    ];

    /** The path every medical-certificate leave points at. */
    private const MC_PATH = 'leaves/sample-medical-certificate.pdf';

    public function run(): void
    {
        $this->ensureSampleCertificate();

        $types = LeaveType::all()->keyBy('name');
        $hr = User::where('email', 'hr@peopleportal.test')->first();
        $today = Carbon::today();
        $created = 0;

        foreach (self::LEAVES as [$email, $typeName, $start, $end, $duration, $reason, $status]) {
            $user = User::with('immediateSupervisor')->where('email', $email)->first();
            $type = $types->get($typeName);

            if (! $user || ! $type) {
                $this->command->warn("Skipped leave for {$email} -- user or leave type '{$typeName}' not found.");
                continue;
            }

            $startDate = $today->copy()->modify($start);
            $endDate = $today->copy()->modify($end);

            $exists = Leave::withTrashed()
                ->where('user_id', $user->id)
                ->whereDate('start_date', $startDate)
                ->where('leaves_type_id', $type->id)
                ->exists();

            if ($exists) {
                continue;
            }

            $approver = $user->immediateSupervisor ?? $hr;

            // Always start pending so the observer records the pending credits.
            $leave = Leave::create([
                'user_id' => $user->id,
                'leaves_type_id' => $type->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration' => $duration,
                'time_in' => $duration === 'Custom' ? '09:00:00' : null,
                'time_out' => $duration === 'Custom' ? '12:30:00' : null,
                'reason' => $reason,
                'notes' => $duration === 'Custom' ? 'Will make up the hours in the afternoon.' : null,
                'attachment' => $typeName === self::SL_MC ? self::MC_PATH : null,
                'status' => 'pending',
                'approver_id' => $approver?->id,
            ]);

            // Then move it to its final state, which is what the observer uses
            // to shift days from pending into used.
            if ($status === 'cancelled_after_approval') {
                $leave->update($this->transitionAttributes('approved', $user, $approver, $startDate));
                $leave->update($this->transitionAttributes('cancelled', $user, $approver, $startDate));
            } elseif ($status !== 'pending') {
                $leave->update($this->transitionAttributes($status, $user, $approver, $startDate));
            }

            $created++;
        }

        $this->command->info("{$created} leaves seeded across pending, approved, rejected and cancelled.");
    }

    /**
     * The seeded leaves reference a certificate file; without it the preview
     * iframe falls through to the SPA catch-all and shows the app itself.
     */
    private function ensureSampleCertificate(): void
    {
        if (Storage::disk('public')->exists(self::MC_PATH)) {
            return;
        }

        // A minimal one-page PDF, enough for the preview to render.
        $body = "BT /F1 16 Tf 60 700 Td (Sample Medical Certificate) Tj ET\n"
            . "BT /F1 11 Tf 60 670 Td (Seeded placeholder - People Portal) Tj ET";
        $objects = [
            "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj",
            "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj",
            "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]"
                . "/Resources<</Font<</F1 5 0 R>>>>/Contents 4 0 R>>endobj",
            "4 0 obj<</Length " . strlen($body) . ">>stream\n{$body}\nendstream endobj",
            "5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer<</Size " . (count($objects) + 1) . "/Root 1 0 R>>\nstartxref\n{$xref}\n%%EOF";

        Storage::disk('public')->put(self::MC_PATH, $pdf);
    }

    private function transitionAttributes(string $status, User $user, ?User $approver, Carbon $startDate): array
    {
        $decidedAt = $startDate->copy()->subDays(3)->setTime(10, 0);

        return match ($status) {
            'approved' => [
                'status' => 'approved',
                'approver_id' => $approver?->id,
                'approved_at' => $decidedAt,
            ],
            'rejected' => [
                'status' => 'rejected',
                'approver_id' => $approver?->id,
                'rejection_note' => 'Team is on a release freeze for those dates. Please re-file for a later week.',
            ],
            'cancelled' => [
                'status' => 'cancelled',
                'cancelled_at' => $decidedAt->copy()->addDay(),
                'cancelled_by' => $user->id,
                'notes' => 'Cancelled by the requester -- plans changed.',
            ],
            default => ['status' => $status],
        };
    }
}
