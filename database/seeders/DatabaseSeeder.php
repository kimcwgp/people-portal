<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The baseline every environment needs: roles and permissions, the reference
 * lists other modules select from, and the accounts you sign in with.
 *
 * This is deliberately free of demo data, so it is safe to run anywhere.
 *
 *   php artisan migrate:fresh --seed              schema + baseline
 *   php artisan db:seed --class=SampleDataSeeder  demo data, on top
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TeamSeeder::class,
            ShiftSeeder::class,
            RolePermissionSeeder::class,
            TestUserSeeder::class,

            // Reference lists the app cannot function without
            LeaveTypeSeeder::class,
            ProjectTypeSeeder::class,
            TimeTypeSeeder::class,
            HolidaySeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('✅ Baseline seeded. Add demo data with:');
        $this->command->line('   php artisan db:seed --class=SampleDataSeeder');
    }
}
