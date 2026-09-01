<?php

namespace App\Console\Commands;

use App\Models\LeaveCredit;
use App\Models\User;
use Illuminate\Console\Command;

class ProcessYearEndCarryover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:process-year-end-carryover {--year= : The year to process (defaults to current year)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process year-end leave carryover. Carries over maximum 5 VL to next year and resets SL to 0.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?? now()->year;
        $nextYear = $year + 1;

        $this->info("Processing year-end carryover from {$year} to {$nextYear}...");

        // Get all users who have leave credits for the current year
        $currentYearCredits = LeaveCredit::where('year', $year)->get();

        if ($currentYearCredits->isEmpty()) {
            $this->warn("No leave credits found for year {$year}");
            return 0;
        }

        $processed = 0;
        $created = 0;
        $updated = 0;

        $this->withProgressBar($currentYearCredits, function ($creditRecord) use ($nextYear, &$processed, &$created, &$updated) {
            $user = $creditRecord->user;
            
            // Calculate VL to carry over (maximum 5 days)
            $vlRemaining = $creditRecord->vl_credits - $creditRecord->vl_used;
            $carryoverAmount = min($vlRemaining, 5);
            
            // Only process if there's something to carry over or if we need to create next year's record
            if ($carryoverAmount > 0 || $user->employee) {
                // Find or create leave credit record for next year
                $nextYearCredit = LeaveCredit::firstOrNew([
                    'user_id' => $user->id,
                    'year' => $nextYear,
                ]);

                $isNew = !$nextYearCredit->exists;

                // Set the carried over amount
                $nextYearCredit->vl_carried_over = $carryoverAmount;
                $nextYearCredit->vl_carried_over_used = 0;
                
                // Initialize other fields if new record
                if ($isNew) {
                    // Check if user is eligible for birthday leave (1 year from hire date)
                    $hireDate = $user->employee->hire_date ?? null;
                    $birthdayLeave = 0;
                    
                    if ($hireDate) {
                        $oneYearFromHire = \Carbon\Carbon::parse($hireDate)->addYear();
                        if (\Carbon\Carbon::create($nextYear, 1, 1)->greaterThanOrEqualTo($oneYearFromHire)) {
                            $birthdayLeave = 1.00;
                        }
                    }
                    
                    $nextYearCredit->vl_credits = 0;
                    $nextYearCredit->vl_used = 0;
                    $nextYearCredit->vl_pending = 0;
                    $nextYearCredit->sl_credits = 0;
                    $nextYearCredit->sl_used = 0;
                    $nextYearCredit->sl_pending = 0;
                    $nextYearCredit->birthday_leave_count = $birthdayLeave;
                }

                $nextYearCredit->save();

                $processed++;
                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        });

        $this->newLine(2);
        $this->info("Year-end carryover processing completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Records Processed', $processed],
                ['New Records Created', $created],
                ['Existing Records Updated', $updated],
            ]
        );

        // Show summary of carryover
        $totalCarriedOver = LeaveCredit::where('year', $nextYear)
            ->sum('vl_carried_over');
        
        $this->info("Total VL carried over to {$nextYear}: {$totalCarriedOver} days");
        
        // Show warning for unused VL
        $lostVL = LeaveCredit::where('year', $year)
            ->selectRaw('SUM(vl_credits - vl_used - 5) as lost_vl')
            ->whereRaw('(vl_credits - vl_used) > 5')
            ->value('lost_vl');
        
        if ($lostVL > 0) {
            $this->warn("⚠️  Total VL lost (not carried over): {$lostVL} days");
            $this->warn("Employees should use their VL before year-end to avoid losing credits!");
        }

        return 0;
    }
}
