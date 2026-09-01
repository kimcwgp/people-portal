<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SyncUsersToEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employees:sync 
                          {--dry-run : Show what would be created without actually creating}
                          {--status=Probationary : Default employment status for new employees}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync users to employees table - create employee records for users who don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $defaultStatus = $this->option('status');

        $this->info('🔍 Checking for users without employee records...');
        $this->newLine();

        // Get users who don't have employee records
        $usersWithoutEmployees = User::whereDoesntHave('employee')
            ->where('email', '!=', 'superadmin@example.com') // Exclude super admin
            ->get();

        if ($usersWithoutEmployees->isEmpty()) {
            $this->info('✅ All users already have employee records!');
            return 0;
        }

        $this->warn("Found {$usersWithoutEmployees->count()} users without employee records:");
        $this->newLine();

        $table = [];
        foreach ($usersWithoutEmployees as $user) {
            $year = Carbon::now()->year;
            $employeeId = 'D' . $year . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT);
            $table[] = [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Will Create' => $employeeId,
                'Status' => $defaultStatus,
            ];
        }

        $this->table(
            ['ID', 'Name', 'Email', 'Will Create', 'Status'],
            $table
        );

        if ($dryRun) {
            $this->newLine();
            $this->comment('🏃 DRY RUN MODE - No records will be created');
            $this->comment('Remove --dry-run flag to actually create employee records');
            return 0;
        }

        $this->newLine();
        if (!$this->confirm('Do you want to create employee records for these users?', true)) {
            $this->comment('Operation cancelled.');
            return 0;
        }

        $this->newLine();
        $this->info('📝 Creating employee records...');
        
        $progressBar = $this->output->createProgressBar($usersWithoutEmployees->count());
        $progressBar->start();

        $created = 0;
        $errors = 0;

        foreach ($usersWithoutEmployees as $user) {
            try {
                $year = Carbon::now()->year;
                $employeeId = 'D' . $year . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT);
                
                Employee::create([
                    'user_id' => $user->id,
                    'employee_id' => $employeeId,
                    'hire_date' => Carbon::now(), // Default to today, can be updated later
                    'employee_status' => 'active',
                    'employment_status' => $defaultStatus,
                    'employment_type' => 'full_time',
                ]);

                $created++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Failed to create employee record for user {$user->id}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Sync completed!');
        $this->newLine();
        $this->line("  Created: {$created}");
        if ($errors > 0) {
            $this->line("  Errors:  {$errors}");
        }
        $this->newLine();
        $this->comment('💡 Note: All new employee records have hire_date set to today.');
        $this->comment('   HR can update hire dates via Employee Regularization page.');

        return 0;
    }
}
