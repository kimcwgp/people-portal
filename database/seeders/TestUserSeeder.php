<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Team;
use App\Models\Shift;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_PASSWORD', 'password');

        $mancomm = Team::firstOrCreate(['name' => 'Mancomm'], ['description' => 'Mancomm Department']);
        $humanResource = Team::firstOrCreate(['name' => 'Human Resource'], ['description' => 'Human Resource Department']);
        $development = Team::firstOrCreate(['name' => 'Development'], ['description' => 'Development Department']);

        $shift = Shift::where('shift_type', 'day')->orderBy('start_time')->first()
            ?? Shift::create(['shift_type' => 'day', 'start_time' => '08:00:00', 'end_time' => '17:00:00']);

        $accounts = [
            ['role' => 'Super Admin', 'name' => 'Super Admin User', 'email' => 'superadmin@peopleportal.test', 'team' => $mancomm,       'reportsTo' => null],
            ['role' => 'Admin',       'name' => 'Admin User',       'email' => 'admin@peopleportal.test',      'team' => $mancomm,       'reportsTo' => 'superadmin@peopleportal.test'],
            ['role' => 'HR',          'name' => 'HR User',          'email' => 'hr@peopleportal.test',         'team' => $humanResource, 'reportsTo' => 'superadmin@peopleportal.test'],
            ['role' => 'Manager',     'name' => 'Manager User',     'email' => 'manager@peopleportal.test',    'team' => $development,   'reportsTo' => 'superadmin@peopleportal.test'],
            ['role' => 'Team Lead',   'name' => 'Team Lead User',   'email' => 'teamlead@peopleportal.test',   'team' => $development,   'reportsTo' => 'manager@peopleportal.test'],
            ['role' => 'Employee',    'name' => 'Employee One',     'email' => 'employee1@peopleportal.test',  'team' => $development,   'reportsTo' => 'teamlead@peopleportal.test'],
            ['role' => 'Employee',    'name' => 'Employee Two',     'email' => 'employee2@peopleportal.test',  'team' => $development,   'reportsTo' => 'teamlead@peopleportal.test'],
        ];

        $created = [];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => bcrypt($password),
                    'glip_url' => null,
                    'team_id' => $account['team']->id,
                    'shift_id' => $shift->id,
                    'immediate_sup_id' => $account['reportsTo']
                        ? $created[$account['reportsTo']]->id
                        : null,
                    'status' => 1,
                    'online' => 0,
                ]
            );

            $role = Role::where('name', $account['role'])->where('guard_name', 'sanctum')->first();

            if ($role) {
                $user->syncRoles([$role]);
            } else {
                $this->command->warn("Role '{$account['role']}' not found -- run RolePermissionSeeder first.");
            }

            $created[$account['email']] = $user;

            $this->command->info("Seeded {$account['role']}: {$account['email']}");
        }

        $mancomm->update(['team_head_id' => $created['superadmin@peopleportal.test']->id]);
        $humanResource->update(['team_head_id' => $created['hr@peopleportal.test']->id]);
        $development->update(['team_head_id' => $created['manager@peopleportal.test']->id]);

        $this->command->info('');
        $this->command->info(count($accounts) . ' test accounts seeded. Password for all of them: ' . $password);
    }
}
