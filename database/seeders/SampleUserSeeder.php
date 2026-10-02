<?php

namespace Database\Seeders;

use App\Models\Shift;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Adds a realistic roster on top of the fixed accounts from TestUserSeeder so
 * every list, filter and approval flow has more than a handful of rows to work
 * with. Re-running only updates the same accounts -- it never duplicates them.
 */
class SampleUserSeeder extends Seeder
{
    /**
     * Accounts are listed supervisor-first so 'reports_to' always resolves.
     */
    private const ACCOUNTS = [
        ['name' => 'Bea Villanueva',    'email' => 'bea.villanueva@peopleportal.test',    'team' => 'Human Resource',     'role' => 'HR',        'shift' => '08:00:00', 'reports_to' => 'hr@peopleportal.test',              'online' => true],
        ['name' => 'Carlo Mendoza',     'email' => 'carlo.mendoza@peopleportal.test',     'team' => 'Project Management', 'role' => 'Manager',   'shift' => '09:00:00', 'reports_to' => 'superadmin@peopleportal.test',      'online' => true],
        ['name' => 'Divina Reyes',      'email' => 'divina.reyes@peopleportal.test',      'team' => 'Quality Assurance',  'role' => 'Team Lead', 'shift' => '09:00:00', 'reports_to' => 'manager@peopleportal.test',         'online' => false],
        ['name' => 'Enrico Santos',     'email' => 'enrico.santos@peopleportal.test',     'team' => 'Development',        'role' => 'Team Lead', 'shift' => '20:00:00', 'reports_to' => 'manager@peopleportal.test',         'online' => true],
        ['name' => 'Faye Dela Cruz',    'email' => 'faye.delacruz@peopleportal.test',     'team' => 'Development',        'role' => 'Employee',  'shift' => '09:00:00', 'reports_to' => 'teamlead@peopleportal.test',        'online' => true],
        ['name' => 'Gabriel Torres',    'email' => 'gabriel.torres@peopleportal.test',    'team' => 'Development',        'role' => 'Employee',  'shift' => '20:00:00', 'reports_to' => 'enrico.santos@peopleportal.test',   'online' => false],
        ['name' => 'Hannah Lim',        'email' => 'hannah.lim@peopleportal.test',        'team' => 'Development',        'role' => 'Employee',  'shift' => '10:00:00', 'reports_to' => 'enrico.santos@peopleportal.test',   'online' => true],
        ['name' => 'Ivan Bautista',     'email' => 'ivan.bautista@peopleportal.test',     'team' => 'Quality Assurance',  'role' => 'Employee',  'shift' => '09:00:00', 'reports_to' => 'divina.reyes@peopleportal.test',    'online' => false],
        ['name' => 'Joyce Ramirez',     'email' => 'joyce.ramirez@peopleportal.test',     'team' => 'Creatives',          'role' => 'Employee',  'shift' => '08:00:00', 'reports_to' => 'carlo.mendoza@peopleportal.test',   'online' => true],
        ['name' => 'Kevin Aguilar',     'email' => 'kevin.aguilar@peopleportal.test',     'team' => 'Client Support',     'role' => 'Employee',  'shift' => '18:00:00', 'reports_to' => 'carlo.mendoza@peopleportal.test',   'online' => true],
        ['name' => 'Liza Domingo',      'email' => 'liza.domingo@peopleportal.test',      'team' => 'Finance',            'role' => 'Employee',  'shift' => '08:00:00', 'reports_to' => 'admin@peopleportal.test',           'online' => false],
        ['name' => 'Miguel Pascual',    'email' => 'miguel.pascual@peopleportal.test',    'team' => 'Sales',              'role' => 'Employee',  'shift' => '09:00:00', 'reports_to' => 'carlo.mendoza@peopleportal.test',   'online' => false],
        ['name' => 'Nadine Ocampo',     'email' => 'nadine.ocampo@peopleportal.test',     'team' => 'Interns',            'role' => 'Employee',  'shift' => '09:00:00', 'reports_to' => 'divina.reyes@peopleportal.test',    'online' => false],
    ];

    /**
     * Teams that get a head once the roster above exists.
     */
    private const TEAM_HEADS = [
        'Project Management' => 'carlo.mendoza@peopleportal.test',
        'Quality Assurance'  => 'divina.reyes@peopleportal.test',
        'Creatives'          => 'joyce.ramirez@peopleportal.test',
        'Client Support'     => 'kevin.aguilar@peopleportal.test',
        'Finance'            => 'liza.domingo@peopleportal.test',
        'Interns'            => 'nadine.ocampo@peopleportal.test',
    ];

    public function run(): void
    {
        $password = env('SEED_PASSWORD', 'password');

        foreach (self::ACCOUNTS as $account) {
            $team = Team::where('name', $account['team'])->first();
            $shift = Shift::where('start_time', $account['shift'])->first();
            $supervisor = User::where('email', $account['reports_to'])->first();

            if (! $team || ! $shift) {
                $this->command->warn("Skipped {$account['email']} -- run TeamSeeder and ShiftSeeder first.");
                continue;
            }

            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => bcrypt($password),
                    'team_id' => $team->id,
                    'shift_id' => $shift->id,
                    'immediate_sup_id' => $supervisor?->id,
                    'status' => 1,
                    'online' => $account['online'] ? 1 : 0,
                ]
            );

            $role = Role::where('name', $account['role'])->where('guard_name', 'sanctum')->first();

            if ($role) {
                $user->syncRoles([$role]);
            } else {
                $this->command->warn("Role '{$account['role']}' not found -- run RolePermissionSeeder first.");
            }
        }

        foreach (self::TEAM_HEADS as $teamName => $headEmail) {
            $team = Team::where('name', $teamName)->first();
            $head = User::where('email', $headEmail)->first();

            if ($team && $head) {
                $team->update(['team_head_id' => $head->id]);
            }
        }

        $this->command->info(count(self::ACCOUNTS) . ' sample users seeded (password: ' . $password . ').');
    }
}
