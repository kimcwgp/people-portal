<?php

namespace App\Http\Controllers;

use App\Traits\HasPagination;
use Illuminate\Http\{JsonResponse, Request};
use Spatie\Permission\Models\{Role, Permission};
use Illuminate\Support\Facades\{Validator, DB};

class RoleController extends Controller
{
    use HasPagination;

    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPageLimit('roles', $request->input('per_page'));

        $query = Role::query()
            ->where('guard_name', 'sanctum')
            ->with('permissions:id,name')
            ->withCount(['permissions', 'users'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%");
            })
            ->when($request->filled('has_users'), function ($q) use ($request) {
                $request->has_users ? $q->has('users') : $q->doesntHave('users');
            })
            ->when($request->filled('start_date') || $request->filled('end_date'), function ($q) use ($request) {
                if ($request->start_date && $request->end_date) {
                    $q->whereBetween('created_at', [
                        \Carbon\Carbon::parse($request->start_date)->startOfDay(),
                        \Carbon\Carbon::parse($request->end_date)->endOfDay(),
                    ]);
                } elseif ($request->start_date) {
                    $q->where('created_at', '>=', \Carbon\Carbon::parse($request->start_date)->startOfDay());
                } elseif ($request->end_date) {
                    $q->where('created_at', '<=', \Carbon\Carbon::parse($request->end_date)->endOfDay());
                }
            });

        $allowedSorts = ['name', 'created_at', 'updated_at', 'permissions_count', 'users_count'];
        $sortBy = $request->input('sort_by', 'name');
        
        $sortDirection = strtolower($request->input('sort_direction', 'asc'));
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $roles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'roles' => $roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name'),
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'created_at' => $role->created_at?->toISOString(),
            ]),
            'pagination' => $this->buildPaginationMeta($roles)
        ]);
    }

    public function getPermissions(Request $request): JsonResponse
    {
        $guard = 'sanctum';

        $allPermissions = Permission::where('guard_name', $guard)
            ->orderBy('name')
            ->get();

        $grouped = $allPermissions->groupBy(function ($permission) {
            $name = $permission->name;
            $parts = explode(' ', $name);
            
            if (count($parts) > 2) {
                if (in_array($parts[1], ['my', 'team', 'proxy'])) {
                    return ucfirst($parts[1]) . ' ' . ucfirst(implode(' ', array_slice($parts, 2)));
                }
                return ucfirst(implode(' ', array_slice($parts, 1)));
            } elseif (count($parts) === 2) {
                return ucfirst($parts[1]);
            }
            
            return 'Other';
        });

        $formattedGroups = [];
        foreach ($grouped as $module => $permissions) {
            $formattedGroups[$module] = [
                'module' => $module,
                'permissions' => $permissions->map(fn($perm) => [
                    'id' => $perm->id,
                    'name' => $perm->name,
                    'action' => explode(' ', $perm->name)[0],
                ])->values()->all()
            ];
        }

        ksort($formattedGroups);

        return response()->json([
            'success' => true,
            'permissions' => $formattedGroups,
            'all_permissions' => $allPermissions->pluck('name')->values()->all(),
            'total_count' => $allPermissions->count(),
            'guard_name' => $guard
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,NULL,id,guard_name,sanctum',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'sanctum'
            ]);

            if ($request->filled('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();

            $role->load('permissions:id,name');

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->pluck('name'),
                    'permissions_count' => $role->permissions->count(),
                    'users_count' => 0,
                    'created_at' => $role->created_at?->toISOString(),
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions:id,name')->loadCount('users');

        return response()->json([
            'success' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name'),
                'permissions_count' => $role->permissions->count(),
                'users_count' => $role->users_count,
                'created_at' => $role->created_at?->toISOString(),
                'updated_at' => $role->updated_at?->toISOString(),
            ]
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id . ',id,guard_name,' . $role->guard_name,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role->update(['name' => $request->name]);

            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            DB::commit();

            $role->load('permissions:id,name')->loadCount('users');

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->pluck('name'),
                    'permissions_count' => $role->permissions->count(),
                    'users_count' => $role->users_count,
                    'updated_at' => $role->updated_at?->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->loadCount('users');

        if ($role->users_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete role that has {$role->users_count} user(s) assigned. Please reassign them first."
            ], 422);
        }

        try {
            $roleName = $role->name;
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => "Role '{$roleName}' deleted successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStats(Request $request): JsonResponse
    {
        $guard = 'sanctum';

        $stats = [
            'total_roles' => Role::where('guard_name', $guard)->count(),
            'total_permissions' => Permission::where('guard_name', $guard)->count(),
            'roles_with_users' => Role::where('guard_name', $guard)->has('users')->count(),
            'empty_roles' => Role::where('guard_name', $guard)->doesntHave('users')->count(),
            'guard_name' => $guard
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    public function bulkAssignPermissions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
            'action' => 'required|in:add,remove,replace'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $roles = Role::whereIn('id', $request->role_ids)->get();
            $action = $request->action;

            foreach ($roles as $role) {
                switch ($action) {
                    case 'add':
                        $role->givePermissionTo($request->permissions);
                        break;
                    case 'remove':
                        $role->revokePermissionTo($request->permissions);
                        break;
                    case 'replace':
                        $role->syncPermissions($request->permissions);
                        break;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Permissions {$action}ed for " . $roles->count() . " role(s) successfully"
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk assign permissions: ' . $e->getMessage()
            ], 500);
        }
    }
}