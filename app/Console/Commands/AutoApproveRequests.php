<?php

namespace App\Console\Commands;

use App\Models\Leave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoApproveRequests extends Command
{
    protected $signature = 'requests:auto-approve';
    protected $description = 'Auto-approve pending leave requests after 3 days';

    public function handle()
    {
        $this->info('Starting auto-approval process...');
        
        $threeDaysAgo = Carbon::now()->subDays(3);
        
        $approvedLeavesCount = $this->autoApproveLeaves($threeDaysAgo);
        
        $this->newLine();
        $this->info("Auto-approval completed:");
        $this->info("- Leaves approved: {$approvedLeavesCount}");
        
        return 0;
    }

    private function autoApproveLeaves(Carbon $threeDaysAgo): int
    {
        $this->info('Checking pending leaves...');
        
        $pendingLeaves = Leave::with(['user', 'user.immediateSupervisor'])
            ->where('status', 'pending')
            ->where('created_at', '<=', $threeDaysAgo)
            ->get();
        
        if ($pendingLeaves->isEmpty()) {
            $this->info('No leaves to auto-approve.');
            return 0;
        }
        
        $this->info("Found {$pendingLeaves->count()} pending leaves to approve.");
        
        $approvedCount = 0;
        
        foreach ($pendingLeaves as $leave) {
            try {
                DB::beginTransaction();
                
                $leave->update([
                    'status' => 'approved',
                    'approved_by' => 0, // 0 indicates auto-approval by system
                    'approved_at' => Carbon::now(),
                    'notes' => 'Auto-approved by system',
                ]);
                
                DB::commit();
                
                $this->info("✓ Auto-approved leave for: {$leave->user->name} (ID: {$leave->id})");
                $approvedCount++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ Failed to auto-approve leave ID {$leave->id}: {$e->getMessage()}");
            }
        }
        
        return $approvedCount;
    }

}
