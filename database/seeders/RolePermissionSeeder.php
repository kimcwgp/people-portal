<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Creating permissions for both web and sanctum guards...');

        // Define all permissions by module
        $permissions = [
            // Profile
            'view profile',
            'edit profile',

            // Dashboard
            'view dashboard',

            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Role Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',

            // Shift Management
            'view shifts',
            'create shifts',
            'edit shifts',
            'delete shifts',

            // Client Management
            'view clients',
            'create clients',
            'edit clients',
            'delete clients',

            // Project Management
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',

            // Leave Type Management
            'view leave types',
            'create leave types',
            'edit leave types',
            'delete leave types',

            // My Attendance
            'view my attendance',
            'export my attendance',

            // My Leaves
            'view my leaves',
            'create my leaves',
            'edit my leaves',
            'cancel my leaves',

            // My Standup
            'view my standups',
            'create my standups',
            'edit my standups',
            'delete my standups',

            // My Overtime
            'view my overtime',
            'create my overtime',
            'cancel my overtime',

            // My Shift
            'view my shift',
            'request shift change',

            // Team Shift
            'view team shift',
            'approve shift requests',

            // Team Attendance
            'view team attendance',
            'export team attendance',

            // Team Leaves
            'view team leaves',
            'export team leaves',

            // Team Standup
            'view team standups',
            'export team standups',

            // Team Overtime
            'view team overtime',
            'export team overtime',

            // HR Announcements
            'view hr announcements',
            'create hr announcements',
            'edit hr announcements',
            'delete hr announcements',

            // Associate Logs
            'view associate logs',
            'create associate logs',
            'delete associate logs',

            // Active Associates
            'view active associates',

            // Proxy Leaves
            'view proxy leaves',
            'create proxy leaves',
            'edit proxy leaves',
            'delete proxy leaves',

            // Proxy Overtime
            'view proxy overtime',
            'create proxy overtime',
            'edit proxy overtime',
            'delete proxy overtime',

            // Proxy Attendance
            'view proxy attendance',
            'create proxy attendance',
            'edit proxy attendance',
            'delete proxy attendance',

            // Leave Credits
            'view leave credits',
            'edit leave credits',

            // Employee Regularization
            'edit employee regularization',
        ];

        // Create permissions for both web and sanctum guards
        $created = 0;
        $guards = ['web', 'sanctum'];

        foreach ($guards as $guard) {
            $this->command->info("Creating permissions for guard: {$guard}");
            foreach ($permissions as $permission) {
                if (Permission::where('name', $permission)->where('guard_name', $guard)->exists()) {
                    // Skip if already exists
                } else {
                    Permission::create(['name' => $permission, 'guard_name' => $guard]);
                    $created++;
                }
            }
        }

        $this->command->info("✅ Created {$created} new permissions");
        $this->command->info('Total permissions: ' . Permission::count());

        // Create roles
        $this->createRolesWithPermissions();

        $this->command->info('✅ All permissions and roles setup completed!');
    }

    private function createRolesWithPermissions()
    {
        $this->command->info('Setting up roles for both guards...');

        $guards = ['web', 'sanctum'];

        foreach ($guards as $guard) {
            $this->command->info("Creating roles for guard: {$guard}");

            // Super Admin - Gets ALL permissions
            $superAdmin = Role::firstOrCreate([
                'name' => 'Super Admin',
                'guard_name' => $guard
            ]);
            $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());
            $this->command->info("  ✅ Super Admin [{$guard}]: " . $superAdmin->permissions->count() . " permissions");

            // Admin - Gets most permissions
            $admin = Role::firstOrCreate([
                'name' => 'Admin',
                'guard_name' => $guard
            ]);
            $adminPermissions = [
                'view profile', 'edit profile', 'view dashboard',
                'view users', 'create users', 'edit users', 'delete users',
                'view roles', 'create roles', 'edit roles', 'delete roles',
                'view shifts', 'create shifts', 'edit shifts', 'delete shifts',
                'view clients', 'create clients', 'edit clients', 'delete clients',
                'view projects', 'create projects', 'edit projects', 'delete projects',
                'view leave types', 'create leave types', 'edit leave types', 'delete leave types',
                'view hr announcements', 'create hr announcements', 'edit hr announcements', 'delete hr announcements',
                'view associate logs', 'create associate logs', 'delete associate logs',
                'view active associates',
                'view leave credits', 'edit leave credits',
                'edit employee regularization',
                'view proxy leaves', 'create proxy leaves', 'edit proxy leaves', 'delete proxy leaves',
                'view proxy overtime', 'create proxy overtime', 'edit proxy overtime', 'delete proxy overtime',
                'view proxy attendance', 'create proxy attendance', 'edit proxy attendance', 'delete proxy attendance',
                'view my attendance', 'export my attendance',
                'view my leaves', 'create my leaves', 'edit my leaves', 'cancel my leaves',
                'view my standups', 'create my standups', 'edit my standups', 'delete my standups',
                'view my overtime', 'create my overtime', 'cancel my overtime',
                'view my shift', 'request shift change',
                'view team shift', 'approve shift requests',
            ];
            $admin->syncPermissions(
                Permission::whereIn('name', $adminPermissions)
                    ->where('guard_name', $guard)
                    ->get()
            );
            $this->command->info("  ✅ Admin [{$guard}]: " . $admin->permissions->count() . " permissions");

            // HR
            $hr = Role::firstOrCreate([
                'name' => 'HR',
                'guard_name' => $guard
            ]);
            $hrPermissions = [
                'view profile', 'edit profile', 'view dashboard',
                'view users', 'create users', 'edit users',
                'view shifts', 'create shifts', 'edit shifts', 'delete shifts',
                'view clients', 'create clients', 'edit clients', 'delete clients',
                'view projects', 'create projects', 'edit projects', 'delete projects',
                'view leave types', 'create leave types', 'edit leave types', 'delete leave types',
                'view hr announcements', 'create hr announcements', 'edit hr announcements', 'delete hr announcements',
                'view associate logs', 'create associate logs', 'delete associate logs',
                'view active associates',
                'view leave credits', 'edit leave credits',
                'edit employee regularization',
                'view proxy leaves', 'create proxy leaves', 'edit proxy leaves', 'delete proxy leaves',
                'view proxy overtime', 'create proxy overtime', 'edit proxy overtime', 'delete proxy overtime',
                'view proxy attendance', 'create proxy attendance', 'edit proxy attendance', 'delete proxy attendance',
                'view my attendance', 'export my attendance',
                'view my leaves', 'create my leaves', 'edit my leaves', 'cancel my leaves',
                'view my standups', 'create my standups', 'edit my standups', 'delete my standups',
                'view my overtime', 'create my overtime', 'cancel my overtime',
                'view my shift', 'request shift change',
                'view team shift', 'approve shift requests',
            ];
            $hr->syncPermissions(
                Permission::whereIn('name', $hrPermissions)
                    ->where('guard_name', $guard)
                    ->get()
            );
            $this->command->info("  ✅ HR [{$guard}]: " . $hr->permissions->count() . " permissions");

            // Manager
            $manager = Role::firstOrCreate([
                'name' => 'Manager',
                'guard_name' => $guard
            ]);
            $managerPermissions = [
                'view profile', 'edit profile', 'view dashboard',
                'view shifts', 'view clients', 'view projects', 'view leave types',
                'view hr announcements',
                'view my attendance', 'export my attendance',
                'view my leaves', 'create my leaves', 'edit my leaves', 'cancel my leaves',
                'view my standups', 'create my standups', 'edit my standups', 'delete my standups',
                'view my overtime', 'create my overtime', 'cancel my overtime',
                'view my shift', 'request shift change',
                'view team attendance', 'export team attendance',
                'view team leaves', 'export team leaves',
                'view team standups', 'export team standups',
                'view team overtime', 'export team overtime',
                'view team shift', 'approve shift requests',
            ];
            $manager->syncPermissions(
                Permission::whereIn('name', $managerPermissions)
                    ->where('guard_name', $guard)
                    ->get()
            );
            $this->command->info("  ✅ Manager [{$guard}]: " . $manager->permissions->count() . " permissions");

            // Employee
            $employee = Role::firstOrCreate([
                'name' => 'Employee',
                'guard_name' => $guard
            ]);
            $employeePermissions = [
                // Profile
                'view profile',
                'edit profile',

                // Dashboard
                'view dashboard',

                // My Attendance (all methods)
                'view my attendance',
                'export my attendance',

                // My Standups (all methods)
                'view my standups',
                'create my standups',
                'edit my standups',
                'delete my standups',

                // My Leaves (all methods)
                'view my leaves',
                'create my leaves',
                'edit my leaves',
                'cancel my leaves',

                // My Overtime (all methods)
                'view my overtime',
                'create my overtime',
                'cancel my overtime',

                // My Shift
                'view my shift',
                'request shift change',
            ];
            $employee->syncPermissions(
                Permission::whereIn('name', $employeePermissions)
                    ->where('guard_name', $guard)
                    ->get()
            );
            $this->command->info("  ✅ Employee [{$guard}]: " . $employee->permissions->count() . " permissions");

            // Team Lead - Employee permissions + My Team modules
            $teamLead = Role::firstOrCreate([
                'name' => 'Team Lead',
                'guard_name' => $guard
            ]);
            $teamLeadPermissions = [
                // Profile
                'view profile',
                'edit profile',

                // Dashboard
                'view dashboard',

                // My Attendance (all methods)
                'view my attendance',
                'export my attendance',

                // My Standups (all methods)
                'view my standups',
                'create my standups',
                'edit my standups',
                'delete my standups',

                // My Leaves (all methods)
                'view my leaves',
                'create my leaves',
                'edit my leaves',
                'cancel my leaves',

                // My Overtime (all methods)
                'view my overtime',
                'create my overtime',
                'cancel my overtime',

                // My Shift
                'view my shift',
                'request shift change',

                // My Team Attendance
                'view team attendance',
                'export team attendance',

                // My Team Standups
                'view team standups',
                'export team standups',

                // My Team Leaves
                'view team leaves',
                'export team leaves',

                // My Team Overtime
                'view team overtime',
                'export team overtime',

                // My Team Shift
                'view team shift',
                'approve shift requests',
            ];
            $teamLead->syncPermissions(
                Permission::whereIn('name', $teamLeadPermissions)
                    ->where('guard_name', $guard)
                    ->get()
            );
            $this->command->info("  ✅ Team Lead [{$guard}]: " . $teamLead->permissions->count() . " permissions");
        }

        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('Total Roles: ' . Role::count());
        $this->command->info('Total Permissions: ' . Permission::count());
        $this->command->info('Total Role-Permission Links: ' . \DB::table('role_has_permissions')->count());
    }
}
