<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\LeaveResource;
use App\Traits\{HasPagination, HasCutoffPeriod};
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProxyLeavesController extends Controller
{
    use HasPagination, HasCutoffPeriod;

    public function index(Request $request)
    {
        $perPage = $this->getPerPageLimit('proxy_leaves', $request->per_page);
        
        $query = Leave::query()
            ->with(['leaveType:id,name', 'user:id,name,email', 'user.currentJobInformation', 'approver:id,name,email'])
            ->select('leaves.*');

        // Check if custom date range is provided
        $hasCustomDateFilter = $request->filled('from_date') || $request->filled('to_date');

        if ($hasCustomDateFilter) {
            // Use custom date range
            if ($request->filled('from_date')) {
                $query->whereDate('leaves.start_date', '>=', $request->from_date);
            }
            
            if ($request->filled('to_date')) {
                $query->whereDate('leaves.end_date', '<=', $request->to_date);
            }
        } else {
            // Use current cut-off period by default
            $cutoff = $this->getCurrentCutoffPeriod();
            $query->whereDate('leaves.start_date', '>=', $cutoff['start']->format('Y-m-d'))
                  ->whereDate('leaves.end_date', '<=', $cutoff['end']->format('Y-m-d'));
        }

        if ($request->status === 'cancelled') {
            $query->onlyTrashed()->where('leaves.status', 'cancelled');
        } else {
            if ($request->filled('status')) {
                $query->where('leaves.status', $request->status);
            }
        }

        if ($request->filled('associate_name')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->associate_name . '%');
            });
        }

        if ($request->filled('approved_by')) {
            $query->whereHas('approver', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->approved_by . '%');
            });
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leaves.leaves_type_id', $request->leave_type_id);
        }

        $leaves = $query
            ->orderBy('leaves.start_date', 'DESC')
            ->orderBy('leaves.created_at', 'DESC')
            ->paginate($perPage);

        // Get current cut-off info for the response
        $cutoff = $hasCustomDateFilter ? null : $this->getCurrentCutoffPeriod();

        return response()->json([
            'data' => LeaveResource::collection($leaves->items()),
            'meta' => array_merge(
                $this->buildPaginationMeta($leaves),
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

        $cacheKey = 'proxy_leaves:form_data:users:' 
            . ($searchQuery ? 'q_' . strtolower($searchQuery) : 'all') 
            . ':limit_' . ($limit ?? 'unlimited');

        $users = Cache::remember($cacheKey, 600, function () use ($searchQuery, $limit) {
            $query = User::select('id', 'name', 'email')
                ->with('currentJobInformation:id,user_id,position_name')
                ->where('status', true)
                ->when($searchQuery, function ($q) use ($searchQuery) {
                    $q->search($searchQuery, ['name', 'email']);
                })
                ->orderBy('name');
            
            return $limit ? $query->limit($limit)->get() : $query->get();
        });

        $leaveTypes = Cache::remember('proxy_leaves:leave_types', 3600, function () {
            return LeaveType::select('id', 'name')
                ->orderBy('name')
                ->get();
        });

        // Get current cut-off period info
        $cutoff = $this->getCurrentCutoffPeriod();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'leave_types' => $leaveTypes,
                'current_cutoff' => [
                    'start_date' => $cutoff['start']->format('Y-m-d'),
                    'end_date' => $cutoff['end']->format('Y-m-d'),
                    'label' => $cutoff['start']->format('M d, Y') . ' - ' . $cutoff['end']->format('M d, Y')
                ]
            ],
            'cached' => true, 
        ]);
    }

    public static function clearFormDataCache()
    {
        Cache::forget('proxy_leaves:form_data:users:all:limit_unlimited');
        Cache::forget('proxy_leaves:leave_types');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leaves_type_id' => 'required|exists:leaves_type,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration' => 'required|in:All Day,Half Day (8am to 12nn),Half Day (1pm to 5pm),Custom',
            'time_in' => 'required_if:duration,Custom|nullable|date_format:H:i',
            'time_out' => 'required_if:duration,Custom|nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
        ]);

        $user = User::select('id', 'name', 'immediate_sup_id')
            ->findOrFail($validated['user_id']);

        if (!$user->immediate_sup_id) {
            return response()->json([
                'success' => false,
                'message' => 'The selected employee does not have an assigned supervisor.',
            ], 422);
        }

        $data = $validated;
        $data['status'] = 'pending';
        $data['approver_id'] = $user->immediate_sup_id;

        if ($data['duration'] !== 'Custom') {
            $data['time_in'] = null;
            $data['time_out'] = null;
        }

        $leave = Leave::create($data);
        $leave->load('leaveType', 'user', 'user.currentJobInformation', 'approver');

        return response()->json([
            'success' => true,
            'message' => 'Leave request filed successfully on behalf of ' . $user->name,
            'data' => new LeaveResource($leave)
        ], 201);
    }

    public function show(Leave $leave)
    {
        $leave->load('leaveType', 'user', 'user.currentJobInformation', 'approver');

        return response()->json([
            'success' => true,
            'data' => new LeaveResource($leave)
        ]);
    }

    public function update(Request $request, Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending leave requests can be updated.',
            ], 422);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leaves_type_id' => 'required|exists:leaves_type,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration' => 'required|in:All Day,Half Day (8am to 12nn),Half Day (1pm to 5pm),Custom',
            'time_in' => 'required_if:duration,Custom|nullable|date_format:H:i',
            'time_out' => 'required_if:duration,Custom|nullable|date_format:H:i',
            'reason' => 'required|string|max:1000',
        ]);

        $data = $validated;

        if ($validated['user_id'] != $leave->user_id) {
            $newUser = User::select('id', 'immediate_sup_id')
                ->find($validated['user_id']);
            if ($newUser && $newUser->immediate_sup_id) {
                $data['approver_id'] = $newUser->immediate_sup_id;
            }
        }

        if ($data['duration'] !== 'Custom') {
            $data['time_in'] = null;
            $data['time_out'] = null;
        }

        $leave->update($data);
        $leave->load('leaveType', 'user', 'user.currentJobInformation', 'approver');

        return response()->json([
            'success' => true,
            'message' => 'Leave request updated successfully',
            'data' => new LeaveResource($leave)
        ]);
    }

    public function destroy(Leave $leave)
    {
        $userName = $leave->user->name ?? 'Unknown User';
        $leave->delete();

        return response()->json([
            'success' => true,
            'message' => "Leave request for {$userName} deleted successfully",
        ]);
    }

    public function export(Request $request)
    {
        $query = Leave::query()
            ->with(['leaveType:id,name', 'user:id,name,email', 'user.currentJobInformation', 'approver:id,name,email'])
            ->select('leaves.*');

        // Check if custom date range is provided
        $hasCustomDateFilter = $request->filled('from_date') || $request->filled('to_date');

        if ($hasCustomDateFilter) {
            // Use custom date range
            if ($request->filled('from_date')) {
                $query->whereDate('leaves.start_date', '>=', $request->from_date);
            }
            
            if ($request->filled('to_date')) {
                $query->whereDate('leaves.end_date', '<=', $request->to_date);
            }
        } else {
            // Use current cut-off period by default
            $cutoff = $this->getCurrentCutoffPeriod();
            $query->whereDate('leaves.start_date', '>=', $cutoff['start']->format('Y-m-d'))
                  ->whereDate('leaves.end_date', '<=', $cutoff['end']->format('Y-m-d'));
        }

        if ($request->status === 'cancelled') {
            $query->onlyTrashed()->where('leaves.status', 'cancelled');
        } else {
            if ($request->filled('status')) {
                $query->where('leaves.status', $request->status);
            }
        }

        if ($request->filled('associate_name')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->associate_name . '%');
            });
        }

        if ($request->filled('approved_by')) {
            $query->whereHas('approver', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->approved_by . '%');
            });
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leaves.leaves_type_id', $request->leave_type_id);
        }

        $leaves = $query
            ->orderBy('leaves.start_date', 'DESC')
            ->orderBy('leaves.created_at', 'DESC')
            ->get();

        // Include cut-off period in filename
        $cutoff = $hasCustomDateFilter ? null : $this->getCurrentCutoffPeriod();
        if ($cutoff) {
            $filenameSuffix = '_' . $cutoff['start']->format('Ymd') . '_to_' . $cutoff['end']->format('Ymd');
        } else {
            $filenameSuffix = '_' . date('Y-m-d_His');
        }
        
        $filename = 'proxy_leaves' . $filenameSuffix . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($leaves) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'ID', 'Employee Name', 'Employee Email', 'Position', 'Leave Type', 
                'Start Date', 'End Date', 'Duration (Days)', 'Time In', 'Time Out', 
                'Status', 'Approver', 'Reason', 'Rejection Note', 'Submitted Date'
            ]);

            foreach ($leaves as $leave) {
                fputcsv($file, [
                    $leave->id,
                    $leave->user->name ?? '',
                    $leave->user->email ?? '',
                    $leave->user->currentJobInformation->position_name ?? '',
                    $leave->leaveType->name ?? '',
                    $leave->start_date ? $leave->start_date->format('M d, Y') : '',
                    $leave->end_date ? $leave->end_date->format('M d, Y') : '',
                    $this->calculateNumericDuration($leave),
                    $leave->time_in ? $this->formatTime($leave->time_in) : '',
                    $leave->time_out ? $this->formatTime($leave->time_out) : '',
                    ucfirst($leave->status),
                    $leave->approver->name ?? '',
                    $leave->reason ?? '',
                    $leave->rejection_note ?? '',
                    $leave->created_at ? $leave->created_at->format('M d, Y h:i A') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getStats()
    {
        return Cache::remember('proxy_leaves:stats', 300, function () {
            $cutoff = $this->getCurrentCutoffPeriod();

            $stats = [
                'total' => Leave::whereDate('start_date', '>=', $cutoff['start']->format('Y-m-d'))
                    ->whereDate('end_date', '<=', $cutoff['end']->format('Y-m-d'))
                    ->count(),
                'pending' => Leave::whereDate('start_date', '>=', $cutoff['start']->format('Y-m-d'))
                    ->whereDate('end_date', '<=', $cutoff['end']->format('Y-m-d'))
                    ->where('status', 'pending')
                    ->count(),
                'approved' => Leave::whereDate('start_date', '>=', $cutoff['start']->format('Y-m-d'))
                    ->whereDate('end_date', '<=', $cutoff['end']->format('Y-m-d'))
                    ->where('status', 'approved')
                    ->count(),
                'rejected' => Leave::whereDate('start_date', '>=', $cutoff['start']->format('Y-m-d'))
                    ->whereDate('end_date', '<=', $cutoff['end']->format('Y-m-d'))
                    ->where('status', 'rejected')
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

    private function calculateNumericDuration($leave)
    {
        if (!$leave->start_date || !$leave->end_date) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($leave->start_date);
        $end = \Carbon\Carbon::parse($leave->end_date);
        $daysDiff = $start->diffInDays($end) + 1;

        if ($leave->duration === 'Half Day (8am to 12nn)' || $leave->duration === 'Half Day (1pm to 5pm)') {
            return 0.5;
        }

        if ($leave->duration === 'Custom' && $leave->time_in && $leave->time_out) {
            list($startHours, $startMinutes) = explode(':', $leave->time_in);
            list($endHours, $endMinutes) = explode(':', $leave->time_out);
            
            $totalMinutes = (($endHours * 60) + $endMinutes) - (($startHours * 60) + $startMinutes);
            $hours = $totalMinutes / 60;
            $days = round($hours / 9, 1);
            
            return $days;
        }

        return $daysDiff;
    }

    private function formatTime($time)
    {
        if (!$time) return '';
        
        list($hours, $minutes) = explode(':', $time);
        $hour = (int)$hours;
        $ampm = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour % 12 ?: 12;
        
        return sprintf('%d:%s %s', $displayHour, $minutes, $ampm);
    }
}