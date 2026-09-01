<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\AttendanceResource;
use App\Traits\{HasPagination, HasCutoffPeriod};
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProxyAttendanceController extends Controller
{
    use HasPagination, HasCutoffPeriod;

    public function index(Request $request)
    {
        $perPage = $this->getPerPageLimit('proxy_attendance', $request->per_page);
        
        $query = Attendance::query()
            ->with(['user:id,name,email,team_id', 'user.currentJobInformation', 'user.team', 'breaks', 'correction']);

        // Check if custom date range is provided
        $hasCustomDateFilter = $request->filled('from_date') || $request->filled('to_date');
        
        if ($hasCustomDateFilter) {
            // Use custom date range
            $query->filterByDateRange($request->from_date, $request->to_date, 'attendance_date');
        } else {
            // Use current cut-off period by default
            $cutoff = $this->getCurrentCutoffPeriod();
            $query->filterByDateRange(
                $cutoff['start']->format('Y-m-d'), 
                $cutoff['end']->format('Y-m-d'), 
                'attendance_date'
            );
        }

        // Filter by team/department
        if ($request->filled('team_id')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('team_id', $request->team_id);
            });
        }

        if ($request->filled('associate_name')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->associate_name . '%');
            });
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->whereNotNull('time_in')
                          ->whereNull('time_out');
                    break;
                case 'awol':
                    $query->whereNull('time_in')
                          ->whereDate('attendance_date', '<=', now());
                    break;
                case 'on_leave':
                    $query->whereHas('user.leaves', function($q) {
                        $q->where('status', 'approved')
                          ->whereDate('start_date', '<=', now())
                          ->whereDate('end_date', '>=', now());
                    });
                    break;
                case 'undertime':
                    $query->whereNotNull('time_in')
                          ->whereNotNull('time_out')
                          ->whereRaw('TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600 < 8')
                          ->whereRaw('TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600 >= 6');
                    break;
            }
        }

        $sortColumn = $request->sort_by ?? 'attendance_date';
        $sortDirection = $request->sort_direction ?? 'desc';
        $query->sortBy($sortColumn, $sortDirection);

        if ($sortColumn === 'attendance_date') {
            $query->orderBy('created_at', 'DESC');
        }

        $attendances = $query->paginate($perPage);

        // Get current cut-off info for the response
        $cutoff = $hasCustomDateFilter ? null : $this->getCurrentCutoffPeriod();

        return response()->json([
            'data' => AttendanceResource::collection($attendances->items()),
            'meta' => array_merge(
                $this->buildPaginationMeta($attendances),
                [
                    'current_cutoff' => $cutoff ? [
                        'start_date' => $cutoff['start']->format('Y-m-d'),
                        'end_date' => $cutoff['end']->format('Y-m-d'),
                        'label' => $cutoff['start']->format('M d, Y') . ' - ' . $cutoff['end']->format('M d, Y')
                    ] : null
                ]
            ),
            'success' => true
        ]);
    }

    public function getFormData(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|min:2|max:50',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $searchQuery = $validated['q'] ?? null;
        $limit = $validated['limit'] ?? null;

        // Cache users with autocomplete support
        $usersCacheKey = 'proxy_attendance:users:' 
            . ($searchQuery ? 'q_' . strtolower($searchQuery) : 'all') 
            . ':limit_' . ($limit ?? 'unlimited');

        $users = Cache::remember($usersCacheKey, 600, function () use ($searchQuery, $limit) {
            $query = User::select('id', 'name', 'email')
                ->with('currentJobInformation:id,user_id,position_name')
                ->where('status', true)
                ->when($searchQuery, function ($q) use ($searchQuery) {
                    $q->search($searchQuery, ['name', 'email']);
                })
                ->orderBy('name');
            
            return $limit ? $query->limit($limit)->get() : $query->get();
        });

        // Cache teams list
        $teams = Cache::remember('proxy_attendance:teams', 1800, function () {
            return \App\Models\Team::select('id', 'name', 'description')
                ->orderBy('name')
                ->get();
        });

        // Get current cut-off period info
        $cutoff = $this->getCurrentCutoffPeriod();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'teams' => $teams,
                'current_cutoff' => [
                    'start_date' => $cutoff['start']->format('Y-m-d'),
                    'end_date' => $cutoff['end']->format('Y-m-d'),
                    'label' => $cutoff['start']->format('M d, Y') . ' - ' . $cutoff['end']->format('M d, Y')
                ]
            ]
        ]);
    }

    public static function clearFormDataCache()
    {
        Cache::forget('proxy_attendance:users:all:limit_unlimited');
        Cache::forget('proxy_attendance:teams');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'attendance_date' => 'required|date',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = User::select('id', 'name')
            ->findOrFail($validated['user_id']);

        $existing = Attendance::where('user_id', $validated['user_id'])
            ->whereDate('attendance_date', $validated['attendance_date'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance record already exists for this date.',
            ], 422);
        }

        $data = $validated;
        $data['shift_id'] = 1;

        $attendance = Attendance::create($data);
        $attendance->load('user', 'user.currentJobInformation', 'breaks', 'correction');

        return response()->json([
            'success' => true,
            'message' => 'Attendance record created successfully for ' . $user->name,
            'data' => new AttendanceResource($attendance)
        ], 201);
    }

    public function show(Attendance $attendance)
    {
        $attendance->load('user', 'user.currentJobInformation', 'breaks', 'correction');

        return response()->json([
            'success' => true,
            'data' => new AttendanceResource($attendance)
        ]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'attendance_date' => 'required|date',
            'time_in' => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['user_id'] != $attendance->user_id || 
            $validated['attendance_date'] != $attendance->attendance_date->format('Y-m-d')) {
            
            $existing = Attendance::where('user_id', $validated['user_id'])
                ->whereDate('attendance_date', $validated['attendance_date'])
                ->where('id', '!=', $attendance->id)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record already exists for this employee and date.',
                ], 422);
            }
        }

        $attendance->update($validated);
        $attendance->load('user', 'user.currentJobInformation', 'breaks', 'correction');

        return response()->json([
            'success' => true,
            'message' => 'Attendance record updated successfully',
            'data' => new AttendanceResource($attendance)
        ]);
    }

    public function destroy(Attendance $attendance)
    {
        $userName = $attendance->user->name ?? 'Unknown User';
        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => "Attendance record for {$userName} deleted successfully",
        ]);
    }

    public function export(Request $request)
    {
        $query = Attendance::query()
            ->with(['user:id,name,email,team_id', 'user.currentJobInformation', 'user.team', 'breaks', 'correction']);

        // Check if custom date range is provided
        $hasCustomDateFilter = $request->filled('from_date') || $request->filled('to_date');
        
        if ($hasCustomDateFilter) {
            // Use custom date range
            $query->filterByDateRange($request->from_date, $request->to_date, 'attendance_date');
        } else {
            // Use current cut-off period by default
            $cutoff = $this->getCurrentCutoffPeriod();
            $query->filterByDateRange(
                $cutoff['start']->format('Y-m-d'), 
                $cutoff['end']->format('Y-m-d'), 
                'attendance_date'
            );
        }

        // Filter by team/department
        if ($request->filled('team_id')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('team_id', $request->team_id);
            });
        }

        if ($request->filled('associate_name')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->associate_name . '%');
            });
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->whereNotNull('time_in')
                          ->whereNull('time_out');
                    break;
                case 'awol':
                    $query->whereNull('time_in')
                          ->whereDate('attendance_date', '<=', now());
                    break;
                case 'on_leave':
                    $query->whereHas('user.leaves', function($q) {
                        $q->where('status', 'approved')
                          ->whereDate('start_date', '<=', now())
                          ->whereDate('end_date', '>=', now());
                    });
                    break;
                case 'undertime':
                    $query->whereNotNull('time_in')
                          ->whereNotNull('time_out')
                          ->whereRaw('TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600 < 7.5')
                          ->whereRaw('TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600 >= 6');
                    break;
            }
        }

        $attendances = $query
            ->sortBy('attendance_date', 'desc')
            ->orderBy('created_at', 'DESC')
            ->get();

        // Include cut-off period and team in filename
        $cutoff = $hasCustomDateFilter ? null : $this->getCurrentCutoffPeriod();
        $teamName = $request->filled('team_id') 
            ? \App\Models\Team::find($request->team_id)?->name 
            : null;
        
        $filenameParts = ['proxy_attendance'];
        
        if ($teamName) {
            $filenameParts[] = str_replace(' ', '_', strtolower($teamName));
        }
        
        if ($cutoff) {
            $filenameParts[] = $cutoff['start']->format('Ymd') . '_to_' . $cutoff['end']->format('Ymd');
        } else {
            $filenameParts[] = date('Y-m-d_His');
        }
        
        $filename = implode('_', $filenameParts) . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'ID', 'Employee Name', 'Employee Email', 'Department/Team', 'Position', 
                'Attendance Date', 'Time In', 'Time Out', 'Working Hours',
                'Break Hours', 'Status', 'Notes', 'Created Date'
            ]);

            foreach ($attendances as $attendance) {
                $resource = new AttendanceResource($attendance);
                $data = $resource->toArray(request());
                
                fputcsv($file, [
                    $attendance->id,
                    $attendance->user->name ?? '',
                    $attendance->user->email ?? '',
                    $attendance->user->team->name ?? 'No Team',
                    $attendance->user->currentJobInformation->position_name ?? '',
                    $attendance->attendance_date ? $attendance->attendance_date->format('M d, Y') : '',
                    $attendance->time_in ?? '',
                    $attendance->time_out ?? '',
                    $data['working_hours'] ?? '--:--',
                    $data['total_break_hours'] ?? '--:--',
                    $data['status'] ?? 'N/A',
                    $data['notes'] ?? '',
                    $attendance->created_at ? $attendance->created_at->format('M d, Y h:i A') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getStats()
    {
        return Cache::remember('proxy_attendance:stats', 300, function () {
            $cutoff = $this->getCurrentCutoffPeriod();

            $stats = [
                'total' => Attendance::whereBetween('attendance_date', [
                    $cutoff['start']->format('Y-m-d'),
                    $cutoff['end']->format('Y-m-d')
                ])->count(),
                
                'active' => Attendance::whereBetween('attendance_date', [
                    $cutoff['start']->format('Y-m-d'),
                    $cutoff['end']->format('Y-m-d')
                ])
                    ->whereNotNull('time_in')
                    ->whereNull('time_out')
                    ->count(),
                
                'awol' => Attendance::whereBetween('attendance_date', [
                    $cutoff['start']->format('Y-m-d'),
                    $cutoff['end']->format('Y-m-d')
                ])
                    ->whereNull('time_in')
                    ->count(),
                
                'undertime' => Attendance::whereBetween('attendance_date', [
                    $cutoff['start']->format('Y-m-d'),
                    $cutoff['end']->format('Y-m-d')
                ])
                    ->whereNotNull('time_in')
                    ->whereNotNull('time_out')
                    ->whereRaw('TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600 < 7.5')
                    ->whereRaw('TIME_TO_SEC(TIMEDIFF(time_out, time_in)) / 3600 >= 6')
                    ->count(),
                
                'cutoff_period' => [
                    'start_date' => $cutoff['start']->format('Y-m-d'),
                    'end_date' => $cutoff['end']->format('Y-m-d'),
                    'label' => $cutoff['start']->format('M d, Y') . ' - ' . $cutoff['end']->format('M d, Y')
                ]
            ];

            return response()->json([   
                'success' => true,
                'data' => $stats
            ]);
        });
    }
}