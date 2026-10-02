<?php

namespace Database\Seeders;

use App\Models\HrAnnouncement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * HR announcements spread over the last few months, with a mix of active and
 * archived posts and one soft-deleted row.
 *
 * The 'image' column holds a storage path; no files are copied, so the
 * thumbnails will 404 until real uploads replace them.
 */
class HrAnnouncementSeeder extends Seeder
{
    /**
     * [title, days from today, views, active]
     */
    private const ANNOUNCEMENTS = [
        ['Updated Hybrid Work Guidelines Effective This Quarter', -120, 412, true],
        ['HMO Renewal: Dependent Enrollment Window Now Open',      -96, 388, true],
        ['Mid-Year Performance Review Schedule',                   -74, 341, true],
        ['Company Outing: Save the Date',                          -58, 507, true],
        ['Reminder: File Your Leaves Through the Portal',          -45, 226, true],
        ['New Timesheet Cut-Off Every Friday',               -33, 198, true],
        ['Payroll Cut-Off Moved for the Holiday Week',             -21, 274, true],
        ['Annual Physical Examination Slots',                      -14, 163, true],
        ['Town Hall: Q3 Business Update',                           -7, 121, true],
        ['Security Reminder: Enable Two-Factor on Your Accounts',   -3,  88, true],
        ['Holiday Party Venue Poll -- Closed',                     -150, 455, false],
        ['Old Dress Code Memo (Superseded)',                       -210, 302, false],
    ];

    public function run(): void
    {
        $author = User::where('email', 'hr@peopleportal.test')->first()
            ?? User::where('email', 'admin@peopleportal.test')->first();

        if (! $author) {
            $this->command->warn('No HR or Admin user found -- skipping announcements.');
            return;
        }

        $today = Carbon::today();
        $created = 0;

        foreach (self::ANNOUNCEMENTS as $index => [$title, $offset, $views, $isActive]) {
            $announcement = HrAnnouncement::withTrashed()->firstOrCreate(
                ['title' => $title],
                [
                    'user_id' => $author->id,
                    'image' => 'announcements/sample-announcement-' . ($index + 1) . '.jpg',
                    'is_active' => $isActive,
                ]
            );

            if (! $announcement->wasRecentlyCreated) {
                continue;
            }

            $postedAt = $today->copy()->addDays($offset)->setTime(9, 0);
            $announcement->views = $views;
            $announcement->created_at = $postedAt;
            $announcement->updated_at = $postedAt;
            $announcement->saveQuietly();

            $created++;
        }

        // One archived post in the recycle bin.
        $trashable = HrAnnouncement::where('title', 'Old Dress Code Memo (Superseded)')->first();
        $trashable?->delete();

        $this->command->info("{$created} HR announcements seeded.");
    }
}
