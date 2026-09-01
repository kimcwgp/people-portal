<?php

namespace App\Http\Controllers;

use App\Models\Overtime;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Resources\OvertimeResource;
use App\Traits\{HasPagination, HasCutoffPeriod};
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProxyOvertimeController extends Controller
{
    use HasPagination, HasCutoffPeriod;

    public function index(Request $request)
    {
        $perPage = $this->getPerPageLimit('proxy_overtime', $request->per_page);
        
        $query = Overtime::query()
            ->with(['project:id,project_name', 'user:id,name,email', 'user.currentJobInformation', 'approver:id,name,email', 'projectManager:id,name,email']);

        // Check if custom date range is provided
        $hasCustomDateFilter = $request->filled('from_date') || $request->filled('to_date');
        
        if ($hasCustomDateFilter) {
            // Use custom date range
            $query->filterByDateRange($request->from_date, $request->to_date, 'ot_date');
        } else {
            // Use current cut-off period by default
            $cutoff = $this->getCurrentCutoffPeriod();
            $query->filterByDateRange(
                $cutoff['start']->format('Y-m-d'), 
                $cutoff['end']->format('Y-m-d'), 
                'ot_date'
            );
        }

        $query->filterByStatus($request->status);
        $query->filterByUser($request->user_id);

        if ($request->filled('associate_name')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->associate_name . '%');
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('approved_by')) {
            $query->whereHas('approver', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->approved_by . '%');
            });
        }

        $sortColumn = $request->sort_by ?? 'ot_date';
        $sortDirection = $request->sort_direction ?? 'desc';
        $query->sortBy($sortColumn, $sortDirection);

        if ($sortColumn === 'ot_date') {
            $query->orderBy('created_at', 'DESC');
        }

        $overtimes = $query->paginate($perPage);

        // Get current cut-off info for the response
        $cutoff = $hasCustomDateFilter ? null : $this->getCurrentCutoffPeriod();

        return response()->json([
            'data' => OvertimeResource::collection($overtimes->items()),
            'meta' => array_merge(
                $this->buildPaginationMeta($overtimes),
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
        $usersCacheKey = 'proxy_overtime:users:' 
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

        // Cache projects list for dropdown (30 minutes - changes less frequently)
        $projects = Cache::remember('proxy_overtime:projects', 1800, function () {
            return Project::select('id', 'project_name')
                ->orderBy('project_name')
                ->get();
        });

        // Get current cut-off period info
        $cutoff = $this->getCurrentCutoffPeriod();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'projects' => $projects,
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
        Cache::forget('proxy_overtime:users:all:limit_unlimited');
        Cache::forget('proxy_overtime:projects');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'ot_date' => 'required|date',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'required|date_format:H:i',
            'notes' => 'required|string|max:1000',
        ]);

        $user = User::select('id', 'name', 'immediate_sup_id')
            ->findOrFail($validated['user_id']);
        $project = Project::select('id', 'pm_id')
            ->findOrFail($validated['project_id']);

        if (!$user->immediate_sup_id) {
            return response()->json([
                'success' => false,
                'message' => 'The selected employee does not have an assigned supervisor.',
            ], 422);
        }

        $data = $validated;
        $data['status'] = 'PENDING';
        $data['approver_id'] = $user->immediate_sup_id;
        $data['project_manager_id'] = $project->pm_id ?? null;

        $overtime = Overtime::create($data);
        $overtime->load('project', 'user', 'user.currentJobInformation', 'approver', 'projectManager');

        return response()->json([
            'success' => true,
            'message' => 'Overtime request filed successfully on behalf of ' . $user->name,
            'data' => new OvertimeResource($overtime)
        ], 201);
    }

    public function show(Overtime $overtime)
    {
        $overtime->load('project', 'user', 'user.currentJobInformation', 'approver', 'projectManager');

        return response()->json([
            'success' => true,
            'data' => new OvertimeResource($overtime)
        ]);
    }

    public function update(Request $request, Overtime $overtime)
    {
        if ($overtime->status !== 'PENDING') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending overtime requests can be updated.',
            ], 422);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'ot_date' => 'required|date',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'required|date_format:H:i',
            'notes' => 'required|string|max:1000',
        ]);

        $data = $validated;

        if ($validated['user_id'] != $overtime->user_id) {
            $newUser = User::select('id', 'immediate_sup_id')
                ->find($validated['user_id']);
            if ($newUser && $newUser->immediate_sup_id) {
                $data['approver_id'] = $newUser->immediate_sup_id;
            }
        }

        if ($validated['project_id'] != $overtime->project_id) {
            $newProject = Project::select('id', 'pm_id')
                ->find($validated['project_id']);
            $data['project_manager_id'] = $newProject->pm_id ?? null;
        }

        $overtime->update($data);
        $overtime->load('project', 'user', 'user.currentJobInformation', 'approver', 'projectManager');

        return response()->json([
            'success' => true,
            'message' => 'Overtime request updated successfully',
            'data' => new OvertimeResource($overtime)
        ]);
    }

    public function destroy(Overtime $overtime)
    {
        $userName = $overtime->user->name ?? 'Unknown User';
        $overtime->delete();

        return response()->json([
            'success' => true,
            'message' => "Overtime request for {$userName} deleted successfully",
        ]);
    }

    public function export(Request $request)
    {
        $query = Overtime::query()
            ->with(['project:id,project_name', 'user:id,name,email', 'user.currentJobInformation', 'approver:id,name,email', 'projectManager:id,name,email']);

        // Check if custom date range is provided
        $hasCustomDateFilter = $request->filled('from_date') || $request->filled('to_date');
        
        if ($hasCustomDateFilter) {
            // Use custom date range
            $query->filterByDateRange($request->from_date, $request->to_date, 'ot_date');
        } else {
            // Use current cut-off period by default
            $cutoff = $this->getCurrentCutoffPeriod();
            $query->filterByDateRange(
                $cutoff['start']->format('Y-m-d'), 
                $cutoff['end']->format('Y-m-d'), 
                'ot_date'
            );
        }

        $query->filterByStatus($request->status);

        if ($request->filled('associate_name')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->associate_name . '%');
            });
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('approved_by')) {
            $query->whereHas('approver', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->approved_by . '%');
            });
        }

        $overtimes = $query
            ->sortBy('ot_date', 'desc')
            ->orderBy('created_at', 'DESC')
            ->get();

        // Include cut-off period in filename
        $cutoff = $hasCustomDateFilter ? null : $this->getCurrentCutoffPeriod();
        if ($cutoff) {
            $filenameSuffix = '_' . $cutoff['start']->format('Ymd') . '_to_' . $cutoff['end']->format('Ymd');
        } else {
            $filenameSuffix = '_' . date('Y-m-d_His');
        }
        
        $filename = 'proxy_overtimes' . $filenameSuffix . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($overtimes) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'ID', 'Employee Name', 'Employee Email', 'Position', 'Project', 
                'OT Date', 'Time In', 'Time Out', 'OT Hours', 
                'Status', 'Approver', 'Project Manager', 'Notes', 'Submitted Date'
            ]);

            foreach ($overtimes as $overtime) {
                fputcsv($file, [
                    $overtime->id,
                    $overtime->user->name ?? '',
                    $overtime->user->email ?? '',
                    $overtime->user->currentJobInformation->position_name ?? '',
                    $overtime->project->project_name ?? '',
                    $overtime->ot_date ? $overtime->ot_date->format('M d, Y') : '',
                    $overtime->time_in ? $overtime->time_in->format('g:i A') : '',
                    $overtime->time_out ? $overtime->time_out->format('g:i A') : '',
                    $overtime->ot_hours ?? 0,
                    $overtime->status,
                    $overtime->approver->name ?? '',
                    $overtime->projectManager->name ?? '',
                    $overtime->notes ?? '',
                    $overtime->created_at ? $overtime->created_at->format('M d, Y h:i A') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function getStats()
    {
        return Cache::remember('proxy_overtime:stats', 300, function () {
            $cutoff = $this->getCurrentCutoffPeriod();

            $stats = [
                'total' => Overtime::whereBetween('ot_date', [
                    $cutoff['start']->format('Y-m-d'),
                    $cutoff['end']->format('Y-m-d')
                ])->count(),
                'pending' => Overtime::whereBetween('ot_date', [
                    $cutoff['start']->format('Y-m-d'),
                    $cutoff['end']->format('Y-m-d')
                ])->filterByStatus('PENDING')->count(),
                'approved' => Overtime::whereBetween('ot_date', [
                    $cutoff['start']->format('Y-m-d'),
                    $cutoff['end']->format('Y-m-d')
                ])->filterByStatus('APPROVED')->count(),
                'rejected' => Overtime::whereBetween('ot_date', [
                    $cutoff['start']->format('Y-m-d'),
                    $cutoff['end']->format('Y-m-d')
                ])->filterByStatus('REJECTED')->count(),
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