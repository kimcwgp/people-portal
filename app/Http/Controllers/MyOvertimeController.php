<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Overtime\StoreOvertimeRequest;
use App\Http\Resources\OvertimeResource;    
use App\Models\{Overtime, Project, User};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyOvertimeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Overtime::query()
            ->with([
                'project:id,project_name', 
                'user:id,name,email', 
                'approver:id,name,email',
                'projectManager:id,name,email'
            ])
            ->forUser(auth()->id());

        if ($request->status === 'CANCELLED') {
            $query = $query->onlyTrashed()->where('status', 'CANCELLED');
        } elseif ($request->status) {
            $query = $query->where('status', $request->status);
        } else {
            $query = $query->withTrashed();
        }

        $overtimes = $query
            ->when($request->start_date, function ($query, $startDate) {
                $query->whereDate('ot_date', '>=', $startDate);
            })
            ->when($request->end_date, function ($query, $endDate) {
                $query->whereDate('ot_date', '<=', $endDate);
            })
            ->when($request->project_id, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->orderBy('ot_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        // Calculate total approved OT hours for the filtered period
        $approvedQuery = Overtime::query()
            ->forUser(auth()->id())
            ->where('status', 'APPROVED')
            ->when($request->start_date, function ($query, $startDate) {
                $query->whereDate('ot_date', '>=', $startDate);
            })
            ->when($request->end_date, function ($query, $endDate) {
                $query->whereDate('ot_date', '<=', $endDate);
            })
            ->when($request->project_id, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            });

        $totalApprovedHours = $approvedQuery->get()->sum('ot_hours');
        
        // Format hours
        $hours = floor($totalApprovedHours);
        $minutes = round(($totalApprovedHours - $hours) * 60);
        $formattedTotal = $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";

        return OvertimeResource::collection($overtimes)->additional([
            'total_approved_ot_hours' => $totalApprovedHours,
            'formatted_total_approved_ot_hours' => $formattedTotal,
        ]);
    }

    public function store(StoreOvertimeRequest $request)
    {
        $currentUser = auth()->user();
        
        if (!$currentUser->immediate_sup_id) {
            return response()->json([
                'message' => 'No immediate supervisor assigned. Please contact HR to set up your supervisor.',
                'errors' => ['approver' => ['No immediate supervisor found for your account.']]
            ], 422);
        }
        $data = $request->validated();
        $data['approver_id'] = $currentUser->immediate_sup_id;

        $overtime = Overtime::create($data);

        $overtime->load(['project:id,project_name', 'user:id,name,email', 'approver:id,name,email', 'projectManager:id,name,email']);

        return new OvertimeResource($overtime);
    }

    public function show(Overtime $overtime): OvertimeResource
    {

        if ($overtime->user_id !== auth()->id()) {
            abort(403, 'Unauthorized to view this overtime record.');
        }

        $overtime->load(['project:id,project_name', 'user:id,name,email', 'approver:id,name,email', 'projectManager:id,name,email']);

        return new OvertimeResource($overtime);
    }

    public function cancel(Request $request, Overtime $overtime)
    {
        if ($overtime->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only cancel your own overtime requests.',
            ], 403);
        }

        if (!in_array($overtime->status, ['PENDING', 'APPROVED'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or approved overtime requests can be cancelled.',
            ], 422);
        }

        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        $overtime->update([
            'status' => 'CANCELLED',
            'cancellation_reason' => $request->cancellation_reason,
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id()
        ]);

        $overtime->delete();

        // Send notification to supervisor
        $user = auth()->user();
        if ($user->immediate_supervisor_id) {
            $supervisor = \App\Models\User::find($user->immediate_supervisor_id);
            if ($supervisor && $supervisor->glip_url) {
                $ringCentral = app(\App\Services\RingCentralService::class);
                $ringCentral->sendOvertimeCancellationNotification(
                    $overtime,
                    $user,
                    $supervisor->glip_url,
                    $request->cancellation_reason
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Overtime request has been cancelled successfully. Your supervisor has been notified.',
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $baseQuery = Overtime::forUser($userId);
        
        // Apply date filters if provided
        if ($request->start_date) {
            $baseQuery = $baseQuery->whereDate('ot_date', '>=', $request->start_date);
        }
        
        if ($request->end_date) {
            $baseQuery = $baseQuery->whereDate('ot_date', '<=', $request->end_date);
        }

        // Apply project filter if provided
        if ($request->project_id) {
            $baseQuery = $baseQuery->where('project_id', $request->project_id);
        }

        $stats = [
            'pending' => (clone $baseQuery)->byStatus('PENDING')->count(),
            'approved' => (clone $baseQuery)->byStatus('APPROVED')->count(),
            'rejected' => (clone $baseQuery)->byStatus('REJECTED')->count(),
            'cancelled' => Overtime::onlyTrashed()
                ->forUser($userId)
                ->where('status', 'CANCELLED')
                ->when($request->start_date, fn($q) => $q->whereDate('ot_date', '>=', $request->start_date))
                ->when($request->end_date, fn($q) => $q->whereDate('ot_date', '<=', $request->end_date))
                ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
                ->count(),
            'total' => Overtime::withTrashed()
                ->forUser($userId)
                ->when($request->start_date, fn($q) => $q->whereDate('ot_date', '>=', $request->start_date))
                ->when($request->end_date, fn($q) => $q->whereDate('ot_date', '<=', $request->end_date))
                ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
                ->count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function statistics(Request $request): JsonResponse
    {
        $query = Overtime::forUser(auth()->id());

        // Apply date filters if provided
        if ($request->start_date) {
            $query->whereDate('ot_date', '>=', $request->start_date);
        }
        
        if ($request->end_date) {
            $query->whereDate('ot_date', '<=', $request->end_date);
        }

        $stats = [
            'total_records' => $query->count(),
            'pending_count' => $query->clone()->byStatus('PENDING')->count(),
            'approved_count' => $query->clone()->byStatus('APPROVED')->count(),
            'rejected_count' => $query->clone()->byStatus('REJECTED')->count(),
            'total_hours' => $query->get()->sum('ot_hours'),
            'approved_hours' => $query->clone()->byStatus('APPROVED')->get()->sum('ot_hours'),
        ];

        return response()->json(['data' => $stats]);
    }

    public function getProjects(): JsonResponse
    {
        try {
            $projects = Project::select('id', 'project_name')
                ->orderBy('project_name')
                ->get();
            
            return response()->json(['data' => $projects]);
        } catch (\Exception $e) {
            \Log::error('Error fetching projects: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to fetch projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSupervisorInfo(): JsonResponse
    {
        try {
            $currentUser = auth()->user();
        
            if (!$currentUser->immediate_sup_id) {
                return response()->json([
                    'data' => null,
                    'message' => 'No immediate supervisor assigned'
                ]);
            }

            $supervisor = User::select('id', 'name', 'email')
                ->find($currentUser->immediate_sup_id);
            
            return response()->json(['data' => $supervisor]);
        } catch (\Exception $e) {
            \Log::error('Error fetching supervisor info: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to fetch supervisor information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProjectManagers(): JsonResponse
    {
        try {
            $projectManagers = User::select('id', 'name', 'email')
                ->where('team_id', 6)
                ->where('status', 1)
                ->orderBy('name')
                ->get();
            
            return response()->json(['data' => $projectManagers]);
        } catch (\Exception $e) {
            \Log::error('Error fetching project managers: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to fetch project managers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}