<?php

namespace App\Console\Commands;

use App\Models\Leave;
use App\Models\Overtime;
use App\Services\RingCentralService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoApproveRequests extends Command
{
    protected $signature = 'requests:auto-approve';
    protected $description = 'Auto-approve pending leaves and overtime requests after 3 days';

    public function __construct(
        protected RingCentralService $ringCentral
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting auto-approval process...');
        
        $threeDaysAgo = Carbon::now()->subDays(3);
        
        $approvedLeavesCount = $this->autoApproveLeaves($threeDaysAgo);
        $approvedOvertimeCount = $this->autoApproveOvertime($threeDaysAgo);
        
        $this->newLine();
        $this->info("Auto-approval completed:");
        $this->info("- Leaves approved: {$approvedLeavesCount}");
        $this->info("- Overtime approved: {$approvedOvertimeCount}");
        
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
                
                // Send notifications
                $this->sendLeaveNotifications($leave);
                
                $this->info("✓ Auto-approved leave for: {$leave->user->name} (ID: {$leave->id})");
                $approvedCount++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ Failed to auto-approve leave ID {$leave->id}: {$e->getMessage()}");
            }
        }
        
        return $approvedCount;
    }

    private function autoApproveOvertime(Carbon $threeDaysAgo): int
    {
        $this->info('Checking pending overtime requests...');
        
        $pendingOvertime = Overtime::with(['user', 'user.immediateSupervisor'])
            ->where('status', 'pending')
            ->where('created_at', '<=', $threeDaysAgo)
            ->get();
        
        if ($pendingOvertime->isEmpty()) {
            $this->info('No overtime requests to auto-approve.');
            return 0;
        }
        
        $this->info("Found {$pendingOvertime->count()} pending overtime requests to approve.");
        
        $approvedCount = 0;
        
        foreach ($pendingOvertime as $overtime) {
            try {
                DB::beginTransaction();
                
                $overtime->update([
                    'status' => 'approved',
                    'approved_by' => 0, // 0 indicates auto-approval by system
                    'approved_at' => Carbon::now(),
                    'notes' => 'Auto-approved by system',
                ]);
                
                DB::commit();
                
                // Send notifications
                $this->sendOvertimeNotifications($overtime);
                
                $this->info("✓ Auto-approved overtime for: {$overtime->user->name} (ID: {$overtime->id})");
                $approvedCount++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("✗ Failed to auto-approve overtime ID {$overtime->id}: {$e->getMessage()}");
            }
        }
        
        return $approvedCount;
    }

    private function sendLeaveNotifications(Leave $leave): void
    {
        $user = $leave->user;
        $supervisor = $user->immediateSupervisor;
        
        // Notify employee
        if ($user && $user->glip_url) {
            $this->ringCentral->sendLeaveAutoApprovalNotification(
                $leave,
                $user,
                $user->glip_url
            );
        }
        
        // Notify supervisor
        if ($supervisor && $supervisor->glip_url) {
            $this->ringCentral->sendLeaveAutoApprovalToSupervisor(
                $leave,
                $user,
                $supervisor->glip_url
            );
        }
    }

    private function sendOvertimeNotifications(Overtime $overtime): void
    {
        $user = $overtime->user;
        $supervisor = $user->immediateSupervisor;
        
        // Notify employee
        if ($user && $user->glip_url) {
            $this->ringCentral->sendOvertimeAutoApprovalNotification(
                $overtime,
                $user,
                $user->glip_url
            );
        }
        
        // Notify supervisor
        if ($supervisor && $supervisor->glip_url) {
            $this->ringCentral->sendOvertimeAutoApprovalToSupervisor(
                $overtime,
                $user,
                $supervisor->glip_url
            );
        }
    }
}
