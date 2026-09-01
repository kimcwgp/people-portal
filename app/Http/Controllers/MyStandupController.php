<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Standup\{StoreStandupRequest, UpdateStandupRequest};
use App\Http\Resources\StandupResource;
use App\Models\{Standup, Project};
use App\Services\RingCentralService;
use App\Traits\HasPagination;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MyStandupController extends Controller
{
    use HasPagination;

    private RingCentralService $ringCentral;

    public function __construct(RingCentralService $ringCentral)
    {
        $this->ringCentral = $ringCentral;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPageLimit('standups', $request->get('per_page'));
        
        $standups = Standup::query()
            ->with(['project:id,project_name', 'user:id,name,email'])
            ->where('user_id', auth()->id())
            ->when($request->start_date, function ($query, $startDate) {
                $query->whereDate('standup_date', '>=', $startDate);
            })
            ->when($request->end_date, function ($query, $endDate) {
                $query->whereDate('standup_date', '<=', $endDate);
            })
            ->when($request->project_id, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->orderBy('standup_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $paginationData = $this->buildPaginationResponse($standups);
        
        return response()->json([
            'data' => StandupResource::collection($standups->items()),
            'meta' => $paginationData
        ]);
    }

    public function store(StoreStandupRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = auth()->user();
        
        DB::beginTransaction();
        
        try {
            $createdStandups = [];
            
            foreach ($validated['standups'] as $standupData) {
                if (isset($standupData['hours']) && isset($standupData['minutes'])) {
                    $totalMinutes = ($standupData['hours'] * 60) + $standupData['minutes'];
                    $standupData['time_spent_minutes'] = $totalMinutes;
                    unset($standupData['hours'], $standupData['minutes']);
                }
                
                $standupData['user_id'] = auth()->id();
                $standupData['standup_date'] = $validated['standup_date'];
                
                $standup = Standup::create($standupData);
                $standup->load(['project:id,project_name,glip_url', 'user:id,name,email']);
                $createdStandups[] = $standup;
            }
            
            DB::commit();
            
            $this->sendStandupNotificationToSupervisor($user, $createdStandups);
            
            $count = count($createdStandups);
            $message = $count === 1 
                ? 'Standup created successfully!' 
                : "{$count} standups created successfully!";
            
            return response()->json([
                'message' => $message,
                'data' => StandupResource::collection(collect($createdStandups)),
                'count' => $count
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Standup creation failed', [
                'user_id' => auth()->id(),
                'data' => $validated,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Failed to create standups. Please try again.',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function update(UpdateStandupRequest $request, Standup $standup): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            if (isset($validated['hours']) && isset($validated['minutes'])) {
                $totalMinutes = ($validated['hours'] * 60) + $validated['minutes'];
                $validated['time_spent_minutes'] = $totalMinutes;
                unset($validated['hours'], $validated['minutes']);
            }
            
            $standup->update($validated);
            $standup->load(['project:id,project_name', 'user:id,name,email']);
            
            return response()->json([
                'message' => 'Standup updated successfully',
                'data' => new StandupResource($standup)
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Standup update failed', [
                'standup_id' => $standup->id,
                'user_id' => auth()->id(),
                'data' => $validated ?? null,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Failed to update standup. Please try again.',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function sendStandupNotificationToSupervisor($user, array $standups): void
    {
        try {
            // 1. Send to immediate supervisor (all standups)
            $supervisor = $user->immediateSupervisor;
            
            if ($supervisor && !empty($supervisor->glip_url)) {
                $success = $this->ringCentral->sendStandupNotification(
                    $user, 
                    $standups, 
                    $supervisor->glip_url
                );
                
                if ($success) {
                    \Log::info('Standup notification sent to supervisor', [
                        'user_id' => $user->id,
                        'supervisor_id' => $supervisor->id,
                        'standups_count' => count($standups)
                    ]);
                } else {
                    \Log::warning('Failed to send standup notification to supervisor', [
                        'user_id' => $user->id,
                        'supervisor_id' => $supervisor->id
                    ]);
                }
            } else {
                \Log::info('No supervisor or supervisor glip_url configured', [
                    'user_id' => $user->id,
                    'has_supervisor' => !is_null($supervisor),
                    'supervisor_has_glip' => $supervisor ? !empty($supervisor->glip_url) : false
                ]);
            }
            
            // 2. Send to project channels (individual standups per project)
            foreach ($standups as $standup) {
                if ($standup->project && !empty($standup->project->glip_url)) {
                    $projectSuccess = $this->ringCentral->sendStandupNotification(
                        $user, 
                        [$standup],  // Send only this standup to the project channel
                        $standup->project->glip_url
                    );
                    
                    if ($projectSuccess) {
                        \Log::info('Standup notification sent to project channel', [
                            'user_id' => $user->id,
                            'project_id' => $standup->project->id,
                            'project_name' => $standup->project->project_name
                        ]);
                    } else {
                        \Log::warning('Failed to send standup notification to project channel', [
                            'user_id' => $user->id,
                            'project_id' => $standup->project->id,
                            'project_name' => $standup->project->project_name
                        ]);
                    }
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Error sending standup notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function show(Standup $standup): StandupResource
    {
        $this->authorize('view', $standup);
        $standup->load(['project:id,project_name', 'user:id,name,email']);
        return new StandupResource($standup);
    }

    public function getByDateRange(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'project_id' => 'sometimes|exists:projects,id',
        ]);

        $standups = Standup::query()
            ->with(['project:id,project_name', 'user:id,name,email'])
            ->where('user_id', auth()->id())
            ->whereBetween('standup_date', [
                $request->start_date,
                $request->end_date
            ])
            ->when($request->project_id, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->orderBy('standup_date', 'desc')
            ->get();

        return StandupResource::collection($standups);
    }

    public function recent(Request $request): AnonymousResourceCollection
    {
        $limit = min($request->get('limit', 5), 10);

        $standups = Standup::query()
            ->with(['project:id,project_name', 'user:id,name,email'])
            ->where('user_id', auth()->id())
            ->orderBy('standup_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return StandupResource::collection($standups);
    }

    public function checkExistence(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'project_id' => 'sometimes|exists:projects,id',
        ]);

        $query = Standup::query()
            ->where('user_id', auth()->id())
            ->whereDate('standup_date', $request->date);

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $exists = $query->exists();
        $standup = $exists ? $query->first() : null;

        return response()->json([
            'exists' => $exists,
            'standup' => $exists ? new StandupResource($standup->load(['project:id,project_name', 'user:id,name,email'])) : null,
        ]);
    }

    public function getAllProjects(): JsonResponse
    {
        try {
            $projects = Project::select('id', 'project_name')
                ->orderBy('project_name')
                ->get();
            
            return response()->json([
                'data' => $projects
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching projects: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to fetch projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}