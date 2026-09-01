<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into a properly structured array for the dynamic dashboard
     */
    public function toArray($request): array
    {
        return [
            'attendance' => $this->getAttendanceData(),
            'stats' => $this->getStatsData(),
            'announcements' => $this->getAnnouncementsData(),
            'requests' => $this->getRequestsData(),
        ];
    }

    /**
     * Get comprehensive attendance data for the dynamic interface
     */
    private function getAttendanceData(): array
    {
        $attendance = $this->resource['attendance'] ?? [];
        
        return [
            // Basic status
            'status' => $attendance['status'] ?? 'OUT',
            'is_logged_in' => $attendance['is_logged_in'] ?? false,
            
            // Time tracking
            'time_in' => $attendance['time_in'] ?? null,
            'time_out' => $attendance['time_out'] ?? null,
            'working_hours' => $attendance['working_hours'] ?? '0h 0m',
            
            // Break information
            'break_time_remaining' => $attendance['break_time_remaining'] ?? '—',
            'active_break' => $this->getActiveBreakData($attendance),
            
            // Notes and additional info
            'daily_notes' => $attendance['daily_notes'] ?? '',
            'today_summary' => $attendance['today_summary'] ?? null,
            
            // Timestamps for debugging/tracking
            'last_action_at' => $attendance['last_action_at'] ?? null,
            'shift_start' => $attendance['shift_start'] ?? null,
            'shift_end' => $attendance['shift_end'] ?? null,
        ];
    }

    /**
     * Get structured active break data
     */
    private function getActiveBreakData(?array $attendance): ?array
    {
        $activeBreak = $attendance['active_break'] ?? null;
        
        if (!$activeBreak) {
            return null;
        }
        
        return [
            'type' => $activeBreak['type'] ?? null,
            'label' => $activeBreak['label'] ?? ucfirst($activeBreak['type'] ?? 'Break'),
            'started_at' => $activeBreak['started_at'] ?? null,
            'allowance_minutes' => $activeBreak['allowance_minutes'] ?? null,
            'deduct_from_work' => $activeBreak['deduct_from_work'] ?? true,
            'elapsed_minutes' => $activeBreak['elapsed_minutes'] ?? 0,
            'remaining_minutes' => $activeBreak['remaining_minutes'] ?? null,
        ];
    }

    /**
     * Get stats data with proper defaults
     */
    private function getStatsData(): array
    {
        $stats = $this->resource['stats'] ?? [];
        
        return [
            'vl_remaining' => $stats['vl_remaining'] ?? 0,
            'sl_remaining' => $stats['sl_remaining'] ?? 0,
            'vl_carried_over_remaining' => $stats['vl_carried_over_remaining'] ?? 0,
            'birthday_leave_available' => $stats['birthday_leave_available'] ?? 1,
            'total_working_hours_today' => $stats['total_working_hours_today'] ?? '0h 0m',
            'total_break_time_today' => $stats['total_break_time_today'] ?? '0m',
            'overtime_hours' => $stats['overtime_hours'] ?? '0m',
            'this_week_hours' => $stats['this_week_hours'] ?? '0h 0m',
        ];
    }

    /**
     * Get announcements with fallback to defaults
     */
    private function getAnnouncementsData(): array
    {
        return $this->resource['announcements'] ?? $this->getDefaultAnnouncements();
    }

    /**
     * Get requests data with proper structure
     */
    private function getRequestsData(): array
    {
        $requests = $this->resource['requests'] ?? [];
        
        // Ensure each request has required fields
        return collect($requests)->map(function ($request) {
            return [
                'id' => $request['id'] ?? null,
                'type' => $request['type'] ?? 'unknown',
                'name' => $request['name'] ?? 'Unknown User',
                'avatar' => $request['avatar'] ?? $this->generateAvatarUrl($request['name'] ?? 'User'),
                'leave_type' => $request['leave_type'] ?? $request['type'] ?? null,
                'start_date' => $request['start_date'] ?? null,
                'end_date' => $request['end_date'] ?? null,
                'duration' => $request['duration'] ?? null,
                'reason' => $request['reason'] ?? null,
                'status' => $request['status'] ?? 'pending',
                'created_at' => $request['created_at'] ?? null,
            ];
        })->toArray();
    }

    /**
     * Generate avatar URL for users without avatars
     */
    private function generateAvatarUrl(string $name): string
    {
        $cleanName = urlencode($name);
        return "https://ui-avatars.com/api/?name={$cleanName}&background=random&color=fff&size=128";
    }

    /**
     * Get default announcements when none are provided
     */
    private function getDefaultAnnouncements(): array
    {
        return [
            [
                'id' => 'default-1',
                'company' => 'People Portal',
                'title' => 'TOWN HALL MEETING',
                'date' => 'September 24, 2025 | 4PM - 5PM',
                'description' => 'Join us for our quarterly town hall meeting to discuss company updates, achievements, and future plans.',
                'bgClass' => 'bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500',
                'priority' => 'high',
                'created_at' => now()->toISOString(),
            ],
            [
                'id' => 'default-2',
                'company' => 'People Portal',
                'title' => 'SYSTEM MAINTENANCE',
                'date' => 'September 20, 2025 | 10PM - 12AM',
                'description' => 'Scheduled system maintenance will occur tonight. Please save your work before 10PM.',
                'bgClass' => 'bg-gradient-to-br from-amber-500 via-orange-500 to-red-500',
                'priority' => 'medium',
                'created_at' => now()->subDay()->toISOString(),
            ]
        ];
    }

    /**
     * Add meta information to the response
     */
    public function with($request): array
    {
        return [
            'meta' => [
                'timestamp' => now()->toISOString(),
                'timezone' => config('app.timezone'),
                'version' => '2.0',
                'user_id' => auth()->id(),
            ]
        ];
    }

    /**
     * Add additional HTTP headers
     */
    public function withResponse($request, $response): void
    {
        $response->header('X-Dashboard-Version', '2.0');
        $response->header('X-Timestamp', now()->toISOString());
    }
}