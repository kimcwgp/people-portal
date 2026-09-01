<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceFilterRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Leave;
use App\Services\RingCentralService;
use App\Traits\HasPagination;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB, Log, Auth};
use Carbon\Carbon;

class MyAttendanceController extends Controller
{
    use HasPagination;

    public function index(AttendanceFilterRequest $request): JsonResponse
    {
        $user = auth()->user();
        $filters = $request->validated();
        $dateRange = $this->getDateRange($filters);
        $query = Attendance::with(['breaks' => function($query) {
                $query->orderBy('started_at', 'asc');
            }, 'correction'])
            ->forUser($user->id)
            ->filterByDateRange(
                $dateRange['start']->format('Y-m-d'),
                $dateRange['end']->format('Y-m-d'),
                'attendance_date'
            );

        if (!empty($filters['month'])) {
            $year = $filters['year'] ?? date('Y');
            $query->filterByMonth($filters['month'], $year, 'attendance_date');
        }

        $existingAttendances = $query
            ->orderBy('attendance_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
            
        $allAttendances = $this->generateCompleteRecords(
            $user, 
            $dateRange['start'], 
            $dateRange['end'], 
            $existingAttendances
        );

        $this->attachLeaveData($user, $allAttendances, $dateRange);
        $perPage = $this->getPerPageLimit('attendance', $filters['per_page'] ?? null);
        $currentPage = $request->get('page', 1);
        $paginationResponse = $this->buildManualPaginationResponse(
            $allAttendances, 
            $currentPage, 
            $perPage
        );
        
        $paginationResponse['data'] = $this->formatAttendanceWithSessions($paginationResponse['data']);

        return response()->json($paginationResponse);
    }

    private function attachLeaveData($user, $allAttendances, $dateRange): void
    {
        $leaves = Leave::with(['leaveType'])
            ->filterByUser($user->id)
            ->filterByDateRange(
                $dateRange['start']->format('Y-m-d'),
                $dateRange['end']->format('Y-m-d'),
                'start_date'
            )
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        $leavesByDate = [];
        foreach ($leaves as $leave) {
            $startDate = Carbon::parse($leave->start_date);
            $endDate = Carbon::parse($leave->end_date);
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                if (!isset($leavesByDate[$dateStr])) {
                    $leavesByDate[$dateStr] = [];
                }
                $leavesByDate[$dateStr][] = $leave;
            }
        }
        foreach ($allAttendances as $attendance) {
            $attendanceDate = Carbon::parse($attendance->attendance_date)->format('Y-m-d');
            $attendance->leaves_for_date = collect($leavesByDate[$attendanceDate] ?? []);
        }
    }

    private function getDateRange(array $filters): array
    {
        if (!empty($filters['startDate']) && !empty($filters['endDate'])) {
            return [
                'start' => Carbon::parse($filters['startDate']),
                'end' => Carbon::parse($filters['endDate'])
            ];
        }

        if (!empty($filters['month'])) {
            $year = $filters['year'] ?? date('Y');
            $month = $filters['month'];
            return [
                'start' => Carbon::create($year, $month, 1),
                'end' => Carbon::create($year, $month, 1)->endOfMonth()
            ];
        }

        // Default to current week
        $now = Carbon::now();
        return [
            'start' => $now->copy()->startOfWeek(Carbon::MONDAY),
            'end' => $now->copy()->endOfWeek(Carbon::SUNDAY)
        ];
    }

    private function generateCompleteRecords($user, Carbon $startDate, Carbon $endDate, $existingAttendances): \Illuminate\Support\Collection
    {
        $attendanceByDate = collect();
        foreach ($existingAttendances as $attendance) {
            $normalizedDate = Carbon::parse($attendance->attendance_date)->format('Y-m-d');
            if (!$attendanceByDate->has($normalizedDate)) {
                $attendanceByDate->put($normalizedDate, collect());
            }
            $attendanceByDate->get($normalizedDate)->push($attendance);
        }
        
        $allAttendances = collect();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->format('Y-m-d');
            
            if ($attendanceByDate->has($dateString)) {
                foreach ($attendanceByDate->get($dateString) as $existingRecord) {
                    $allAttendances->push($existingRecord);
                }
            } else {
                $attendance = new Attendance([
                    'id' => null,
                    'user_id' => $user->id,
                    'attendance_date' => $dateString,
                    'time_in' => null,
                    'time_out' => null,
                    'notes' => null,
                ]);
                $attendance->setRelation('breaks', collect());
                $allAttendances->push($attendance);
            }
        }

        return $allAttendances->sortByDesc('attendance_date');
    }

    public function requestCorrection(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'attendance_id' => 'required|exists:attendances,id',
                'corrected_time_in' => 'nullable|date_format:H:i',
                'corrected_time_out' => 'nullable|date_format:H:i',
                'corrected_lunch_start' => 'nullable|date_format:H:i',
                'corrected_lunch_end' => 'nullable|date_format:H:i',
                'reason' => 'required|string|max:500',
            ]);

            // Ensure at least one time field is provided
            if (empty($validated['corrected_time_in']) && 
                empty($validated['corrected_time_out']) && 
                empty($validated['corrected_lunch_start']) && 
                empty($validated['corrected_lunch_end'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please provide at least one corrected time'
                ], 422);
            }

            $user = Auth::user();
            $attendance = Attendance::where('id', $validated['attendance_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Check if there's already a pending correction
            $existingCorrection = AttendanceCorrection::where('attendance_id', $attendance->id)
                ->where('status', 'pending')
                ->first();

            if ($existingCorrection) {
                return response()->json([
                    'success' => false,
                    'message' => 'There is already a pending correction request for this attendance record'
                ], 422);
            }

            $correction = null;
            DB::transaction(function () use ($validated, $user, $attendance, &$correction) {
                $correction = AttendanceCorrection::create([
                    'attendance_id' => $attendance->id,
                    'user_id' => $user->id,
                    'corrected_time_in' => $validated['corrected_time_in'] ?? null,
                    'corrected_time_out' => $validated['corrected_time_out'] ?? null,
                    'corrected_lunch_start' => $validated['corrected_lunch_start'] ?? null,
                    'corrected_lunch_end' => $validated['corrected_lunch_end'] ?? null,
                    'reason' => $validated['reason'],
                    'status' => 'pending',
                ]);
            });

            $correction->load(['attendance', 'attendance.breaks']);

            $supervisor = $user->immediateSupervisor;
            if ($supervisor && $supervisor->glip_url) {
                $ringCentral = app(RingCentralService::class);
                $ringCentral->sendAttendanceCorrectionRequestNotification(
                    $correction,
                    $user,
                    $supervisor->glip_url
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Attendance correction request submitted successfully',
                'data' => [
                    'id' => $correction->id,
                    'status' => $correction->status,
                    'corrected_time_in' => $correction->corrected_time_in,
                    'corrected_time_out' => $correction->corrected_time_out,
                    'corrected_lunch_start' => $correction->corrected_lunch_start,
                    'corrected_lunch_end' => $correction->corrected_lunch_end,
                    'reason' => $correction->reason,
                    'created_at' => $correction->created_at,
                    'updated_at' => $correction->updated_at,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Request attendance correction error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit attendance correction request'
            ], 500);
        }
    }

    private function formatAttendanceWithSessions($attendances): array
    {
        // Group by date
        $grouped = collect($attendances)->groupBy(function($attendance) {
            return Carbon::parse($attendance->attendance_date)->format('Y-m-d');
        });

        $result = [];
        foreach ($grouped as $date => $sessions) {
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
