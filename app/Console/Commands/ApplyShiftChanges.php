<?php

namespace App\Console\Commands;

use App\Models\ShiftChangeRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApplyShiftChanges extends Command
{
    protected $signature = 'shifts:apply-changes';
    protected $description = 'Apply approved shift change requests whose effective date has arrived';

    public function handle()
    {
        $today = Carbon::today();

        $requests = ShiftChangeRequest::with(['user', 'requestedShift'])
            ->where('status', 'approved')
            ->where('effective_date', '<=', $today)
            ->get();

        if ($requests->isEmpty()) {
            $this->info('No shift changes to apply today.');
            return 0;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($requests as $request) {
            try {
                DB::beginTransaction();

                $request->user->update([
                    'shift_id' => $request->requested_shift_id,
                ]);

                $request->update([
                    'status' => 'applied',
                ]);

                DB::commit();

                $this->info("✓ Applied shift change for user: {$request->user->name} (ID: {$request->user->id})");
                $successCount++;

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ Failed to apply shift change for user ID {$request->user_id}: {$e->getMessage()}");
                $failureCount++;
            }
        }

        $this->info("\nShift changes applied: {$successCount} succeeded, {$failureCount} failed.");

        return 0;
    }
}
