<?php

namespace App\Http\Controllers;

use App\Models\{User, Team, Shift};
use App\Http\Requests\UserManagement\{StoreUserRequest, UpdateUserRequest};
use App\Http\Resources\UserResource;
use App\Services\UserService;
use App\Traits\HasPagination;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use HasPagination;

    public function __construct(
        private UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPageLimit('users', $request->input('per_page'));

        $query = User::query()->with(['team', 'shift', 'immediateSupervisor', 'roles']);

        $this->applyFilters($query, $request);

        $query->sortBy(
            $request->input('sort_by', 'created_at'),
            strtolower($request->input('sort_direction', 'desc'))
        );

        $users = $query->paginate($perPage);

        return response()->json([
            'users' => [
                ...$this->buildPaginationResponse($users),
                'data' => UserResource::collection($users->items()),
            ],
            'filters' => $this->getFilterOptions(),
            'stats' => $this->userService->getUserStats(),
        ]);
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('team_id')) {
            $query->filterByTeam($request->input('team_id'));
        }

        if ($request->filled('shift_id')) {
            $query->filterByShift($request->input('shift_id'));
        }

        if ($request->filled('supervisor_id')) {
            $query->filterBySupervisor($request->input('supervisor_id'));
        }

        // Role filter
        if ($request->filled('role_id')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('roles.id', $request->input('role_id'));
            });
        }

        if ($request->has('status')) {
            $query->filterByActiveStatus($request->input('status'));
        }

        if ($request->has('online')) {
            $query->filterByOnlineStatus($request->input('online'));
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->filterByDateRange(
                $request->input('start_date'),
                $request->input('end_date'),
                'created_at'
            );
        }

        if ($request->boolean('without_team')) {
            $query->withoutTeam();
        }

        if ($request->boolean('without_supervisor')) {
            $query->withoutSupervisor();
        }

        return $query;
    }

    private function getFilterOptions(): array
    {
        return Cache::remember('user_filter_options', 3600, function () {
            return [
                'teams' => Team::select('id', 'name')->orderBy('name')->get(),
                'shifts' => Shift::select('id', 'shift_type', 'start_time', 'end_time')
                    ->orderBy('shift_type')
                    ->get()
                    ->map(function ($shift) {
                        return [
                            'id' => $shift->id,
                            'shift_type' => $shift->shift_type,
                            'start_time' => $shift->start_time->format('g:i A'),
                            'end_time' => $shift->end_time->format('g:i A'),
                            'label' => ucfirst($shift->shift_type) . ' (' .
                                       $shift->start_time->format('g:i A') . ' - ' .
                                       $shift->end_time->format('g:i A') . ')',
                        ];
                    }),
                'supervisors' => $this->userService->getPotentialSupervisors(),
                'roles' => Role::where('guard_name', 'sanctum')
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get()
                    ->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                        ];
                    }),
            ];
        });
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => new UserResource($user->load(['team', 'shift', 'immediateSupervisor', 'roles']))
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['team', 'shift', 'immediateSupervisor', 'directSubordinates', 'roles']);
        
        return response()->json([
            'user' => new UserResource($user),
            'hierarchy' => $this->userService->getSupervisorHierarchy($user),
            'subordinates' => $this->userService->getDirectSubordinates($user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $updatedUser = $this->userService->updateUser($user, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'user' => new UserResource($updatedUser->load(['team', 'shift', 'immediateSupervisor', 'roles']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            $this->userService->deleteUser($user);
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,assign_team,assign_role,remove_role',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'team_id' => 'required_if:action,assign_team|nullable|exists:teams,id',
            'role_id' => 'required_if:action,assign_role,remove_role|nullable|exists:roles,id',
        ]);

        $result = $this->userService->performBulkAction(
            $request->action,
            $request->user_ids,
            $request->only(['team_id', 'role_id'])
        );
        
        return response()->json($result);
    }

    public function export(Request $request): JsonResponse
    {
        $query = User::query()->with(['team', 'shift', 'immediateSupervisor', 'roles']);
        
        $this->applyFilters($query, $request);

        $query->sortBy(
            $request->input('sort_by', 'created_at'),
            strtolower($request->input('sort_direction', 'desc'))
        );

        $users = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Export ready',
            'count' => $users->count(),
            'data' => UserResource::collection($users)
        ]);
    }

    public function getSupervisorHierarchy(User $user): JsonResponse
    {
        return response()->json([
            'hierarchy' => $this->userService->getSupervisorHierarchy($user),
            'subordinates' => $this->userService->getDirectSubordinates($user)
        ]);
    }

    public function validateSupervisor(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'supervisor_id' => 'required|exists:users,id',
        ]);

        $isValid = $this->userService->validateSupervisorAssignment(
            $request->user_id,
            $request->supervisor_id
        );

        return response()->json([
            'valid' => $isValid,
            'message' => $isValid 
                ? 'Valid supervisor assignment' 
                : 'Invalid supervisor assignment. This would create a circular reference.'
        ]);
    }
}