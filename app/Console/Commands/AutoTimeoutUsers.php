<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoTimeoutUsers extends Command
{
    protected $signature = 'attendance:auto-timeout';
    protected $description = 'Automatically timeout day shift users after 10 PM';

    public function __construct(
        protected AttendanceService $attendanceService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting auto-timeout process...');

        // Get all users who are currently clocked in
        $clockedInUsers = User::whereHas('attendances', function ($query) {
            $query->whereNotNull('time_in')
                  ->whereNull('time_out');
        })->with('shift')->get();

        if ($clockedInUsers->isEmpty()) {
            $this->info('No users currently clocked in.');
            return 0;
        }

        $this->info("Found {$clockedInUsers->count()} clocked-in users. Checking for auto-timeout...");

        $timedOutCount = 0;
        $skippedCount = 0;

        foreach ($clockedInUsers as $user) {
            try {
                // Check if user should be auto timed out
                if ($this->attendanceService->shouldAutoTimeout($user)) {
                    DB::beginTransaction();
                    
                    $result = $this->attendanceService->performAutoTimeout($user);
                    
                    DB::commit();
                    
                    $this->info("✓ Auto timed out: {$user->name} (ID: {$user->id})");
                    $timedOutCount++;
                } else {
                    $this->line("- Skipped: {$user->name} (Not eligible for auto-timeout)");
                    $skippedCount++;
                }

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ Failed to timeout user {$user->name} (ID: {$user->id}): {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Auto-timeout completed: {$timedOutCount} timed out, {$skippedCount} skipped.");

        return 0;
    }
}
