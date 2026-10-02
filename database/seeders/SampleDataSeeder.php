<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\ProjectType;
use App\Models\TimeType;
use App\Models\Shift;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Demo data for manual and exploratory testing: a full roster with 201-file
 * records, clients and projects, and several weeks of attendance, leaves,
 * standups and requests in every status.
 *
 * Not part of the baseline. Run it explicitly, on top of `php artisan db:seed`:
 *   php artisan db:seed --class=SampleDataSeeder
 *
 * Every child seeder matches on natural keys, so re-running tops the data up
 * instead of duplicating it.
 */
class SampleDataSeeder extends Seeder
{
    private const SEEDERS = [
        SampleUserSeeder::class,
        EmployeeProfileSeeder::class,
        ClientSeeder::class,
        ProjectSeeder::class,
        LeaveCreditSeeder::class,
        LeaveSeeder::class,
        AttendanceSeeder::class,
        StandupSeeder::class,
        ShiftChangeRequestSeeder::class,
        TimesheetSeeder::class,
        HrAnnouncementSeeder::class,
        AssociateLogSeeder::class,
        ForApprovalSeeder::class,
    ];

    public function run(): void
    {
        if (! $this->baselineIsPresent()) {
            return;
        }

        $this->call(self::SEEDERS);

        $this->command->newLine();
        $this->command->info('✅ Sample data seeded.');
    }

    /**
     * The sample data hangs off teams, shifts, roles and leave types. Bail out
     * with a clear message rather than a foreign key error if any are missing.
     */
    private function baselineIsPresent(): bool
    {
        $missing = [];

        if (Team::count() === 0) {
            $missing[] = 'TeamSeeder';
        }

        if (Shift::count() === 0) {
            $missing[] = 'ShiftSeeder';
        }

        if (Role::count() === 0) {
            $missing[] = 'RolePermissionSeeder';
        }

        if (LeaveType::count() === 0) {
            $missing[] = 'LeaveTypeSeeder';
        }

        if (ProjectType::count() === 0) {
            $missing[] = 'ProjectTypeSeeder';
        }

        if (TimeType::count() === 0) {
            $missing[] = 'TimeTypeSeeder';
        }

        if ($missing === []) {
            return true;
        }

        $this->command->error('Sample data needs the base seeders first: ' . implode(', ', $missing));
        $this->command->line('Run `php artisan db:seed` first to lay down the baseline.');

        return false;
    }
}
