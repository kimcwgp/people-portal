<?php

namespace App\Services;

use App\Models\{User, Team};
use Illuminate\Support\Facades\{DB, Hash};
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserService
{
    public function getUserStats(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('status', true)->count(),
            'inactive_users' => User::where('status', false)->count(),
            'online_users' => User::where('online', true)->count(),
            'users_without_team' => User::whereNull('team_id')->count(),
            'users_without_supervisor' => User::whereNull('immediate_sup_id')->count(),
        ];
    }

    public function getPotentialSupervisors()
    {
        return User::select('id', 'name', 'email')
            ->where('status', true)
            ->orderBy('name')
            ->get();
    }

    public function createUser(array $data): User
    {
        DB::beginTransaction();

        try {
            if (empty($data['password'])) {
                $data['password'] = Str::random(32);
            }

            $data['password'] = Hash::make($data['password']);

            if (isset($data['immediate_sup_id']) && $data['immediate_sup_id']) {
                if (!$this->validateSupervisorAssignment(0, $data['immediate_sup_id'])) {
                    throw new \Exception('Invalid supervisor assignment');
                }
            }

            $user = User::create($data);

            if (isset($data['role_id'])) {
                $role = Role::findById($data['role_id'], 'sanctum');
                $user->assignRole($role);

                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            }

            // Auto-create employee record (except for super admin)
            if ($user->email !== 'superadmin@example.com') {
                $hireDate = $data['hire_date'] ?? \Carbon\Carbon::now();
                $year = \Carbon\Carbon::parse($hireDate)->year;
                $employeeId = 'D' . $year . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT);
                
                \App\Models\Employee::create([
                    'user_id' => $user->id,
                    'employee_id' => $employeeId,
                    'hire_date' => $hireDate,
                    'employee_status' => 'active',
                    'employment_status' => $data['employment_status'] ?? 'Probationary',
                    'employment_type' => $data['employment_type'] ?? 'full_time',
                ]);
            }

            DB::commit();

            return $user;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateUser(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            if (isset($data['immediate_sup_id']) && $data['immediate_sup_id']) {
                if (!$this->validateSupervisorAssignment($user->id, $data['immediate_sup_id'])) {
                    throw new \Exception('Invalid supervisor assignment. This would create a circular reference.');
                }
            }

            $user->update($data);

            if (isset($data['role_id'])) {
                $role = Role::findById($data['role_id'], 'sanctum');
                $user->syncRoles([$role]);

                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            }

            DB::commit();

            return $user->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteUser(User $user): void
    {
        DB::beginTransaction();

        try {
            if ($user->id === auth()->id()) {
                throw new \Exception('You cannot delete your own account');
            }

            if (Team::where('team_head_id', $user->id)->exists()) {
                throw new \Exception('Cannot delete user who is assigned as team head. Please reassign the team first.');
            }

            if ($user->directSubordinates()->exists()) {
                throw new \Exception('Cannot delete user who has direct subordinates. Please reassign them first.');
            }

            $user->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function performBulkAction(string $action, array $userIds, array $params = []): array
    {
        DB::beginTransaction();

        try {
            if (in_array($action, ['deactivate', 'delete']) && in_array(auth()->id(), $userIds)) {
                throw new \Exception('You cannot perform this action on your own account');
            }

            $affectedCount = 0;
            $message = '';

            switch ($action) {
                case 'activate':
                    $affectedCount = User::whereIn('id', $userIds)->update(['status' => true]);
                    $message = "Successfully activated {$affectedCount} user(s)";
                    break;

                case 'deactivate':
                    $affectedCount = User::whereIn('id', $userIds)->update(['status' => false]);
                    $message = "Successfully deactivated {$affectedCount} user(s)";
                    break;

                case 'assign_team':
                    if (!isset($params['team_id'])) {
                        throw new \Exception('Team ID is required for team assignment');
                    }

                    $affectedCount = User::whereIn('id', $userIds)->update(['team_id' => $params['team_id']]);

                    if ($params['team_id']) {
                        $teamName = Team::find($params['team_id'])->name ?? 'Unknown';
                        $message = "Successfully assigned {$affectedCount} user(s) to team '{$teamName}'";
                    } else {
                        $message = "Successfully removed team assignment from {$affectedCount} user(s)";
                    }
                    break;

                case 'assign_role':
                    if (!isset($params['role_id'])) {
                        throw new \Exception('Role ID is required for role assignment');
                    }

                    $role = Role::findById($params['role_id'], 'sanctum');
                    $users = User::whereIn('id', $userIds)->get();

                    foreach ($users as $user) {
                        $user->assignRole($role);
                        $affectedCount++;
                    }

                    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

                    $message = "Successfully assigned role '{$role->name}' to {$affectedCount} user(s)";
                    break;

                case 'remove_role':
                    if (!isset($params['role_id'])) {
                        throw new \Exception('Role ID is required for role removal');
                    }

                    $role = Role::findById($params['role_id'], 'sanctum');
                    $users = User::whereIn('id', $userIds)->get();

                    foreach ($users as $user) {
                        $user->removeRole($role);
                        $affectedCount++;
                    }

                    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

                    $message = "Successfully removed role '{$role->name}' from {$affectedCount} user(s)";
                    break;

                default:
                    throw new \Exception('Invalid bulk action');
            }

            DB::commit();

            return [
                'success' => true,
                'message' => $message,
                'affected_count' => $affectedCount
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'affected_count' => 0
            ];
        }
    }

    public function getSupervisorHierarchy(User $user): array
    {
        $hierarchy = [];
        $current = $user->immediateSupervisor;
        
        while ($current) {
            $hierarchy[] = [
                'id' => $current->id,
                'name' => $current->name,
                'email' => $current->email,
                'team' => $current->team ? $current->team->name : null,
            ];
            $current = $current->immediateSupervisor;
        }
        
        return $hierarchy;
    }

    public function getDirectSubordinates(User $user)
    {
        return $user->directSubordinates()
            ->select('id', 'name', 'email', 'status')
            ->with('team:id,name')
            ->get();
    }

    public function getAllSubordinates(User $user)
    {
        return $user->getAllSubordinates();
    }

    public function validateSupervisorAssignment(int $userId, int $supervisorId): bool
    {
        if ($userId === $supervisorId) {
            return false;
        }

        if ($userId === 0) {
            return User::where('id', $supervisorId)->where('status', true)->exists();
        }

        $supervisor = User::find($supervisorId);
        $visitedIds = [];

        while ($supervisor && $supervisor->immediate_sup_id) {
            if (in_array($supervisor->immediate_sup_id, $visitedIds)) {
                break;
            }

            $visitedIds[] = $supervisor->immediate_sup_id;

            if ($supervisor->immediate_sup_id == $userId) {
                return false;
            }

            $supervisor = User::find($supervisor->immediate_sup_id);
        }

        return true;
    }

    public function canBeSupervisor(User $user): bool
    {
        return $user->status === true;
    }

    public function reassignSubordinates(User $oldSupervisor, ?int $newSupervisorId): int
    {
        DB::beginTransaction();

        try {
            if ($newSupervisorId && !$this->canBeSupervisor(User::find($newSupervisorId))) {
                throw new \Exception('The new supervisor must be an active user');
            }

            $affectedCount = User::where('immediate_sup_id', $oldSupervisor->id)
                ->update(['immediate_sup_id' => $newSupervisorId]);

            DB::commit();

            return $affectedCount;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}