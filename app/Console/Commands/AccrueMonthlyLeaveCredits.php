<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{User, LeaveCredit};
use Carbon\Carbon;

class AccrueMonthlyLeaveCredits extends Command
{
    protected $signature = 'leave:accrue-monthly {--year=} {--month=} {--user=}';

    protected $description = 'Accrue monthly leave credits (1.25 VL + 1.25 SL) for regular employees';

    public function handle()
    {
        $year = $this->option('year') ?: now()->year;
        $month = $this->option('month') ?: now()->month;
        $userId = $this->option('user');
        
        $this->info("Processing monthly leave accrual for {$year}-{$month}");
        
        $query = User::with(['personalInformation', 'employee'])
            ->whereHas('employee', function($q) {
                $q->where('employee_status', 'active')
                  ->regular();
            });
            
        if ($userId) {
            $query->where('id', $userId);
        }
        
        $users = $query->get();
        
        if ($users->isEmpty()) {
            $this->warn('No regular employees found.');
            return;
        }
        
        $this->info("Found {$users->count()} regular employee(s)");
        
        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();
        
        $processed = 0;
        $skipped = 0;
        
        foreach ($users as $user) {
            // Check if user is eligible for birthday leave (1 year from hire date)
            $hireDate = $user->employee->hire_date ?? null;
            $birthdayLeave = 0;
            
            if ($hireDate) {
                $oneYearFromHire = Carbon::parse($hireDate)->addYear();
                if (Carbon::now()->greaterThanOrEqualTo($oneYearFromHire)) {
                    $birthdayLeave = 1.00;
                }
            }
            
            $leaveCredit = LeaveCredit::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'year' => $year
                ],
                [
                    'vl_credits' => 0,
                    'sl_credits' => 0,
                    'vl_used' => 0,
                    'sl_used' => 0,
                    'vl_pending' => 0,
                    'sl_pending' => 0,
                    'vl_carried_over' => 0,
                    'vl_carried_over_used' => 0,
                    'birthday_leave_count' => $birthdayLeave,
                ]
            );
            
            $leaveCredit->vl_credits += 1.25;
            $leaveCredit->sl_credits += 1.25;
            $leaveCredit->save();
            
            $processed++;
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("✓ Accrued 1.25 VL + 1.25 SL for {$processed} employee(s)");
        
        if ($skipped > 0) {
            $this->warn("⚠ Skipped {$skipped} employee(s)");
        }
        
        return Command::SUCCESS;
    }
}
