<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Team;
use App\Models\Shift;
use App\Models\Employee;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_PASSWORD', 'password');

        // Teams
        $mancomm = Team::firstOrCreate(
            ['name' => 'Mancomm'],
            ['description' => 'Mancomm Department']
        );

        $humanResource = Team::firstOrCreate(
            ['name' => 'Human Resource'],
            ['description' => 'Human Resource Department']
        );

        $development = Team::firstOrCreate(
            ['name' => 'Development'],
            ['description' => 'Development Department']
        );

        // Test accounts
        $accounts = [
            [
                'role' => 'Super Admin',
                'name' => 'Super Admin User',
                'email' => 'superadmin@peopleportal.test',
                'team' => $mancomm,
                'reportsTo' => null,
            ],
            [
                'role' => 'Admin',
                'name' => 'Admin User',
                'email' => 'admin@peopleportal.test',
                'team' => $mancomm,
                'reportsTo' => 'superadmin@peopleportal.test',
            ],
            [
                'role' => 'HR',
                'name' => 'HR User',
                'email' => 'hr@peopleportal.test',
                'team' => $humanResource,
                'reportsTo' => 'superadmin@peopleportal.test',
            ],
            [
                'role' => 'Manager',
                'name' => 'Manager User',
                'email' => 'manager@peopleportal.test',
                'team' => $development,
                'reportsTo' => 'superadmin@peopleportal.test',
            ],
            [
                'role' => 'Team Lead',
                'name' => 'Team Lead User',
                'email' => 'teamlead@peopleportal.test',
                'team' => $development,
                'reportsTo' => 'manager@peopleportal.test',
            ],
            [
                'role' => 'Employee',
                'name' => 'Employee One',
                'email' => 'employee1@peopleportal.test',
                'team' => $development,
                'reportsTo' => 'teamlead@peopleportal.test',
            ],
            [
                'role' => 'Employee',
                'name' => 'Employee Two',
                'email' => 'employee2@peopleportal.test',
                'team' => $development,
                'reportsTo' => 'teamlead@peopleportal.test',
            ],
        ];

        $created = [];

        // Create users
        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => bcrypt($password),
                    'team_id' => $account['team']->id,
                    'immediate_sup_id' => $account['reportsTo']
                        ? $created[$account['reportsTo']]->id
                        : null,
                    'status' => 1,
                    'online' => 0,
                ]
            );

            // Assign role
            $role = Role::where('name', $account['role'])
                ->where('guard_name', 'sanctum')
                ->first();

            if ($role) {
                $user->syncRoles([$role]);
            } else {
                $this->command->warn(
                    "Role '{$account['role']}' not found -- run RolePermissionSeeder first."
                );
            }

            $created[$account['email']] = $user;

            $this->command->info(
                "Seeded {$account['role']}: {$account['email']}"
            );
        }

        // Assign team heads
        $mancomm->update([
            'team_head_id' => $created['superadmin@peopleportal.test']->id,
        ]);

        $humanResource->update([
            'team_head_id' => $created['hr@peopleportal.test']->id,
        ]);

        $development->update([
            'team_head_id' => $created['manager@peopleportal.test']->id,
        ]);

        $users = collect($created)->values();

        // Employee options
        $employmentStatuses = [
            'Probationary',
            'Regular',
            'Turnover',
        ];

        $employeeLeaveTypes = [
            'fixed',
            'accrual',
        ];

        foreach ($users as $user) {

            $shiftTypes = [
                'fixed',
                'flexible',
            ];

            $shiftType = $shiftTypes[array_rand($shiftTypes)];

            $startHour = rand(6, 22);
            $startMinute = [0, 30][array_rand([0, 30])];

            $startTime = now()->setTime(
                $startHour,
                $startMinute,
                0
            );

            $endTime = (clone $startTime)->addHours(9);

            Shift::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'shift_type' => $shiftType,
                    'start_time' => $startTime->format('H:i:s'),
                    'end_time' => $endTime->format('H:i:s'),
                ]
            );

            $hireDate = now()
                ->subYears(rand(1, 5))
                ->subDays(rand(1, 300))
                ->startOfDay();

            $regularizationDate = (clone $hireDate)
                ->addMonths(rand(3, 6));

            if ($regularizationDate->isFuture()) {
                $regularizationDate = null;
            }

            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_id' => 'EMP-' . str_pad($user->id,5,'0',STR_PAD_LEFT),

                    'hire_date' => $hireDate->toDateString(),

                    'regularization_date' => $regularizationDate
                        ? $regularizationDate->toDateString()
                        : null,

                    'employee_leave_type' => $employeeLeaveTypes[
                        array_rand($employeeLeaveTypes)
                    ],

                    'employment_status' => $employmentStatuses[
                        array_rand($employmentStatuses)
                    ],
                ]
            );

            $this->command->info(
                "Assigned {$shiftType} shift to {$user->email}: " .
                $startTime->format('g:i A') . ' - ' .
                $endTime->format('g:i A')
            );
        }

        $this->command->info('');
        $this->command->info(
            count($accounts) . ' test accounts seeded.'
        );

        $this->command->info(
            'Employee records created for all test users.'
        );

        $this->command->info(
            'Random shifts assigned to all test users.'
        );

        $this->command->info(
            'Password for all accounts: ' . $password
        );
    }
}