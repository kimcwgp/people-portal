<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceFilterRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use App\Traits\HasPagination;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeamAttendanceController extends Controller
{
    use HasPagination;

    public function index(AttendanceFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $hasFilters = ($filters['start_date'] ?? null) ||
                     ($filters['end_date'] ?? null) ||
                     ($filters['startDate'] ?? null) ||
                     ($filters['endDate'] ?? null) ||
                     ($filters['user_name'] ?? null);

        if ($hasFilters) {
            return $this->getFilteredAttendance($request);
        }

        return $this->getTodayAttendance($request);
    }

    private function getFilteredAttendance(AttendanceFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        if (($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) ||
            ($filters['startDate'] ?? null) || ($filters['endDate'] ?? null)) {
            $startDate = Carbon::parse($filters['start_date'] ?? $filters['startDate']);
            $endDate = Carbon::parse($filters['end_date'] ?? $filters['endDate']);
        } else {
            $now = Carbon::now();
            $startDate = $now->copy()->startOfWeek(Carbon::MONDAY);
            $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY);
        }

        $teamQuery = User::select('id', 'name', 'email')
            ->where('immediate_sup_id', auth()->id());

        if (!empty($filters['user_name'])) {
            $teamQuery->where('name', 'LIKE', "%{$filters['user_name']}%");
        }

        $teamMembers = $teamQuery->get();
        
        if ($teamMembers->isEmpty()) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $this->getPerPageLimit('team_attendance'),
                'total' => 0,
                'from' => 0,
                'to' => 0
            ]);
        }

        $allRecords = $this->generateCompleteRecords($teamMembers, $startDate, $endDate);
        $perPage = $this->getPerPageLimit('team_attendance', $filters['per_page'] ?? null);
        $currentPage = $request->get('page', 1);

        $paginationResponse = $this->buildManualPaginationResponse(
            $allRecords,
            $currentPage,
            $perPage
        );

        $paginationResponse['data'] = $this->formatAttendanceWithSessions($paginationResponse['data']);

        return response()->json($paginationResponse);
    }

    private function getTodayAttendance(AttendanceFilterRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $teamMembers = User::select('id', 'name', 'email')
            ->where('immediate_sup_id', auth()->id())
            ->get();

        if ($teamMembers->isEmpty()) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $this->getPerPageLimit('team_attendance'),
                'total' => 0,
                'from' => 0,
                'to' => 0
            ]);
        }

        $today = Carbon::today();
        $allRecords = $this->generateCompleteRecords($teamMembers, $today, $today);
        $perPage = $this->getPerPageLimit('team_attendance', $filters['per_page'] ?? null);
        $currentPage = $request->get('page', 1);

        $paginationResponse = $this->buildManualPaginationResponse(
            $allRecords,
            $currentPage,
            $perPage
        );

        $paginationResponse['data'] = $this->formatAttendanceWithSessions($paginationResponse['data']);

        return response()->json($paginationResponse);
    }


    private function generateCompleteRecords(Collection $teamMembers, Carbon $startDate, Carbon $endDate): Collection
    {
        $allRecords = collect();

        $existingAttendances = Attendance::with(['user:id,name,email', 'breaks', 'correction'])
            ->whereIn('user_id', $teamMembers->pluck('id'))
            ->filterByDateRange(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'attendance_date'
            )
            ->orderBy('attendance_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $attendancesByUserAndDate = $existingAttendances->groupBy(function ($attendance) {
            return $attendance->user_id . '_' . $attendance->attendance_date->format('Y-m-d');
        });

        foreach ($teamMembers as $user) {
            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                $dateKey = $user->id . '_' . $currentDate->format('Y-m-d');

                if (isset($attendancesByUserAndDate[$dateKey])) {
                    foreach ($attendancesByUserAndDate[$dateKey] as $attendance) {
                        $allRecords->push($attendance);
                    }
                } else {
                    $attendance = new Attendance();
                    $attendance->id = null;
                    $attendance->user_id = $user->id;
                    $attendance->attendance_date = $currentDate->copy();
                    $attendance->time_in = null;
                    $attendance->time_out = null;
                    $attendance->notes = null;
                    $attendance->created_at = $currentDate->copy();
                    $attendance->updated_at = $currentDate->copy();

                    $attendance->setRelation('user', $user);
                    $attendance->setRelation('breaks', collect());
                    $allRecords->push($attendance);
                }

                $currentDate->addDay();
            }
        }

        $this->addLeaveInformation($allRecords);

        return $allRecords->sortByDesc(function ($attendance) {
            return $attendance->attendance_date->format('Y-m-d') . '_' . $attendance->user->name;
        })->values();
    }

    private function addLeaveInformation(Collection $attendances): void
    {
        if ($attendances->isEmpty()) {
            return;
        }

        $userIds = $attendances->pluck('user_id')->unique();
        $dates = $attendances->pluck('attendance_date')->map(function($date) {
            return $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');
        })->unique();

        $leaves = Leave::with('leaveType')
            ->whereIn('user_id', $userIds)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($dates) {
                foreach ($dates as $date) {
                    $query->orWhere(function ($q) use ($date) {
                        $q->where('start_date', '<=', $date)
                          ->where('end_date', '>=', $date);
                    });
                }
            })
            ->get()
            ->groupBy('user_id');

        foreach ($attendances as $attendance) {
            $userLeaves = $leaves->get($attendance->user_id, collect());
            $attendanceDate = $attendance->attendance_date instanceof Carbon
                ? $attendance->attendance_date
                : Carbon::parse($attendance->attendance_date);

            $leavesForDate = $userLeaves->filter(function ($leave) use ($attendanceDate) {
                return $attendanceDate->between(
                    Carbon::parse($leave->start_date),
                    Carbon::parse($leave->end_date)
                );
            });

            $attendance->leaves_for_date = $leavesForDate;
        }
    }

    public function export(AttendanceFilterRequest $request): StreamedResponse
    {
        $filters = $request->validated();

        if (($filters['start_date'] ?? null) || ($filters['end_date'] ?? null) ||
            ($filters['startDate'] ?? null) || ($filters['endDate'] ?? null)) {
            $startDate = Carbon::parse($filters['start_date'] ?? $filters['startDate']);
            $endDate = Carbon::parse($filters['end_date'] ?? $filters['endDate']);
        } else if (!empty($filters['user_name'])) {
            $now = Carbon::now();
            $startDate = $now->copy()->startOfWeek(Carbon::MONDAY);
            $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY);
        } else {
            $startDate = Carbon::today();
            $endDate = Carbon::today();
        }

        $teamQuery = User::select('id', 'name', 'email')
            ->where('immediate_sup_id', auth()->id());

        if (!empty($filters['user_name'])) {
            $teamQuery->where('name', 'LIKE', "%{$filters['user_name']}%");
        }

        $teamMembers = $teamQuery->get();
        $allRecords = $this->generateCompleteRecords($teamMembers, $startDate, $endDate);

        $exportData = $allRecords->map(function ($attendance) {
            $resource = new AttendanceResource($attendance);
            $data = $resource->toArray(request());

            return [
                'Employee Name' => $data['user']['name'] ?? 'N/A',
                'Email' => $data['user']['email'] ?? 'N/A',
                'Date' => $attendance->attendance_date->format('Y-m-d'),
                'Day' => $attendance->attendance_date->format('l'),
                'Time In' => $data['time_in'] ? Carbon::parse($data['time_in'])->format('H:i') : '--:--',
                'Time Out' => $data['time_out'] ? Carbon::parse($data['time_out'])->format('H:i') : '--:--',
                'Working Hours' => $data['working_hours'] ?? '--:--',
                'Break Hours (BRB)' => $data['total_break_hours'] ?? '--:--',
                'Lunch Break Hours' => $data['lunch_break_hours'] ?? '--:--',
                'Status' => ucfirst(str_replace('_', ' ', $data['status'])),
                'Leave Type' => $data['leave_info']['leave_type'] ?? '',
                'Leave Reason' => $data['leave_info']['reason'] ?? '',
                'Notes' => $data['notes'] ?? '',
            ];
        });

        $filename = 'team_attendance_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($exportData) {
            $file = fopen('php://output', 'w');

            if ($exportData->isNotEmpty()) {
                fputcsv($file, array_keys($exportData->first()));
            }

            foreach ($exportData as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    private function formatAttendanceWithSessions($attendances): array
    {
        // Group by user and date
        $grouped = collect($attendances)->groupBy(function($attendance) {
            $userId = $attendance->user_id ?? $attendance->user->id ?? 'unknown';
            $date = $attendance->attendance_date instanceof Carbon 
                ? $attendance->attendance_date->format('Y-m-d')
                : Carbon::parse($attendance->attendance_date)->format('Y-m-d');
            return $userId . '_' . $date;
        });

        $result = [];
        foreach ($grouped as $key => $sessions) {
            if ($sessions->count() === 1) {
                $result[] = new AttendanceResource($sessions->first());
            } else {
                $allSessions = [];
                foreach ($sessions as $session) {
                    $sessionResource = new AttendanceResource($session);
                    $sessionData = $sessionResource->toArray(request());
                    $allSessions[] = $sessionData;
                }
                
                $firstSession = new AttendanceResource($sessions->first());
                $firstSessionData = $firstSession->toArray(request());
                $firstSessionData['sessions'] = $allSessions;
                
                $result[] = $firstSessionData;
            }
        }

        return $result;
    }
}
