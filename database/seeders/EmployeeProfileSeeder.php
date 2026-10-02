<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmploymentHistory;
use App\Models\JobInformation;
use App\Models\PersonalInformation;
use App\Models\SalaryInformation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Fills the employee 201-file tables for every seeded user: employees,
 * personal_information, job_information, salary_information and the
 * employment_history audit trail.
 *
 * Users that were promoted get a second (older) job_information and
 * salary_information row so "current vs. history" screens have something to
 * show; those older rows are written with an earlier created_at because both
 * models resolve the current record with latest().
 */
class EmployeeProfileSeeder extends Seeder
{
    /**
     * email => profile. 'promotion' is optional and describes the row the
     * person held *before* their current one.
     */
    private const PROFILES = [
        'superadmin@peopleportal.test' => [
            'hire' => '2018-01-15', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Chief Operating Officer', 'level' => 'Executive', 'career' => 'Executive', 'band' => 'Band 6', 'zone' => 'Zone A', 'manager' => true,
            'salary' => 250000, 'allowances' => 25000, 'bonuses' => 120000,
            'dob' => '1980-04-12', 'gender' => 'male', 'marital' => 'married', 'spouse' => 'Marisol Aquino', 'children' => 2,
        ],
        'admin@peopleportal.test' => [
            'hire' => '2019-03-04', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Operations Manager', 'level' => 'Managerial', 'career' => 'Manager', 'band' => 'Band 5', 'zone' => 'Zone A', 'manager' => true,
            'salary' => 150000, 'allowances' => 15000, 'bonuses' => 60000,
            'dob' => '1985-09-23', 'gender' => 'female', 'marital' => 'married', 'spouse' => 'Renato Cruz', 'children' => 1,
        ],
        'hr@peopleportal.test' => [
            'hire' => '2019-06-17', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'HR Manager', 'level' => 'Managerial', 'career' => 'Manager', 'band' => 'Band 5', 'zone' => 'Zone B', 'manager' => true,
            'salary' => 120000, 'allowances' => 12000, 'bonuses' => 45000,
            'dob' => '1987-11-02', 'gender' => 'female', 'marital' => 'single', 'spouse' => null, 'children' => 0,
        ],
        'manager@peopleportal.test' => [
            'hire' => '2020-02-03', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Engineering Manager', 'level' => 'Managerial', 'career' => 'Manager', 'band' => 'Band 5', 'zone' => 'Zone A', 'manager' => true,
            'salary' => 160000, 'allowances' => 16000, 'bonuses' => 70000,
            'dob' => '1984-07-19', 'gender' => 'male', 'marital' => 'married', 'spouse' => 'Katrina Yulo', 'children' => 3,
        ],
        'teamlead@peopleportal.test' => [
            'hire' => '2021-05-10', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Software Engineering Team Lead', 'level' => 'Senior', 'career' => 'Lead', 'band' => 'Band 4', 'zone' => 'Zone B', 'manager' => true,
            'salary' => 110000, 'allowances' => 9000, 'bonuses' => 30000,
            'dob' => '1990-01-28', 'gender' => 'female', 'marital' => 'single', 'spouse' => null, 'children' => 0,
            'promotion' => ['date' => '2023-07-01', 'position' => 'Senior Software Engineer', 'level' => 'Senior', 'career' => 'Individual Contributor', 'band' => 'Band 3', 'zone' => 'Zone B', 'salary' => 88000],
        ],
        'employee1@peopleportal.test' => [
            'hire' => '2022-08-01', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Software Engineer', 'level' => 'Mid', 'career' => 'Individual Contributor', 'band' => 'Band 3', 'zone' => 'Zone C', 'manager' => false,
            'salary' => 75000, 'allowances' => 6000, 'bonuses' => 18000,
            'dob' => '1994-03-15', 'gender' => 'male', 'marital' => 'single', 'spouse' => null, 'children' => 0,
        ],
        'employee2@peopleportal.test' => [
            'hire' => '2023-01-16', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Software Engineer', 'level' => 'Mid', 'career' => 'Individual Contributor', 'band' => 'Band 3', 'zone' => 'Zone C', 'manager' => false,
            'salary' => 68000, 'allowances' => 6000, 'bonuses' => 15000,
            'dob' => '1995-12-06', 'gender' => 'female', 'marital' => 'married', 'spouse' => 'Paolo Rivera', 'children' => 1,
        ],
        'bea.villanueva@peopleportal.test' => [
            'hire' => '2023-04-03', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'HR Associate', 'level' => 'Junior', 'career' => 'Individual Contributor', 'band' => 'Band 2', 'zone' => 'Zone C', 'manager' => false,
            'salary' => 45000, 'allowances' => 4000, 'bonuses' => 9000,
            'dob' => '1997-05-30', 'gender' => 'female', 'marital' => 'single', 'spouse' => null, 'children' => 0,
        ],
        'carlo.mendoza@peopleportal.test' => [
            'hire' => '2020-09-14', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Project Manager', 'level' => 'Managerial', 'career' => 'Manager', 'band' => 'Band 5', 'zone' => 'Zone B', 'manager' => true,
            'salary' => 140000, 'allowances' => 14000, 'bonuses' => 50000,
            'dob' => '1986-02-11', 'gender' => 'male', 'marital' => 'married', 'spouse' => 'Andrea Mendoza', 'children' => 2,
        ],
        'divina.reyes@peopleportal.test' => [
            'hire' => '2021-11-08', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'QA Team Lead', 'level' => 'Senior', 'career' => 'Lead', 'band' => 'Band 4', 'zone' => 'Zone B', 'manager' => true,
            'salary' => 95000, 'allowances' => 8000, 'bonuses' => 24000,
            'dob' => '1991-08-21', 'gender' => 'female', 'marital' => 'married', 'spouse' => 'Jomar Reyes', 'children' => 1,
            'promotion' => ['date' => '2024-01-01', 'position' => 'Senior QA Engineer', 'level' => 'Senior', 'career' => 'Individual Contributor', 'band' => 'Band 3', 'zone' => 'Zone B', 'salary' => 78000],
        ],
        'enrico.santos@peopleportal.test' => [
            'hire' => '2021-02-15', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Development Team Lead (Night Shift)', 'level' => 'Senior', 'career' => 'Lead', 'band' => 'Band 4', 'zone' => 'Zone B', 'manager' => true,
            'salary' => 105000, 'allowances' => 12000, 'bonuses' => 28000,
            'dob' => '1989-10-04', 'gender' => 'male', 'marital' => 'married', 'spouse' => 'Cielo Santos', 'children' => 2,
            'promotion' => ['date' => '2023-03-01', 'position' => 'Senior Software Engineer', 'level' => 'Senior', 'career' => 'Individual Contributor', 'band' => 'Band 3', 'zone' => 'Zone B', 'salary' => 86000],
        ],
        'faye.delacruz@peopleportal.test' => [
            'hire' => '2023-06-05', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Software Engineer', 'level' => 'Mid', 'career' => 'Individual Contributor', 'band' => 'Band 3', 'zone' => 'Zone C', 'manager' => false,
            'salary' => 70000, 'allowances' => 6000, 'bonuses' => 14000,
            'dob' => '1996-06-18', 'gender' => 'female', 'marital' => 'single', 'spouse' => null, 'children' => 0,
            'promotion' => ['date' => '2025-01-01', 'position' => 'Junior Software Engineer', 'level' => 'Junior', 'career' => 'Individual Contributor', 'band' => 'Band 2', 'zone' => 'Zone C', 'salary' => 52000],
        ],
        'gabriel.torres@peopleportal.test' => [
            'hire' => '2024-01-08', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Software Engineer', 'level' => 'Mid', 'career' => 'Individual Contributor', 'band' => 'Band 3', 'zone' => 'Zone C', 'manager' => false,
            'salary' => 62000, 'allowances' => 7000, 'bonuses' => 12000,
            'dob' => '1998-09-09', 'gender' => 'male', 'marital' => 'single', 'spouse' => null, 'children' => 0,
        ],
        'hannah.lim@peopleportal.test' => [
            'hire' => '2025-02-17', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'Junior Software Engineer', 'level' => 'Junior', 'career' => 'Individual Contributor', 'band' => 'Band 2', 'zone' => 'Zone C', 'manager' => false,
            'salary' => 48000, 'allowances' => 4000, 'bonuses' => 8000,
            'dob' => '2000-01-25', 'gender' => 'female', 'marital' => 'single', 'spouse' => null, 'children' => 0,
        ],
        'ivan.bautista@peopleportal.test' => [
            'hire' => '2024-07-01', 'type' => 'full_time', 'status' => 'Regular',
            'position' => 'QA Engineer', 'level' => 'Mid', 'career' => 'Individual Contributor', 'band' => 'Band 3', 'zone' => 'Zone C', 'manager' => false,
            'salary' => 55000, 'allowances' => 5000, 'bonuses' => 10000,
            'dob' => '1996-11-13', 'gender' => 'male', 'marital' => 'married', 'spouse' => 'Trisha Bautista', 'children' => 1,
        ],
        'joyce.ramirez@peopleportal.test' => [
            'hire' => '2022-10-10', 'type' => 'part_time', 'status' => 'Regular',
            'position' => 'Senior Graphic Designer', 'level' => 'Senior', 'career' => 'Individual Contributor', 'band' => 'Band 3', 'zone' => 'Zone C', 'manager' => false,
            'salary' => 52000, 'allowances' => 3000, 'bonuses' => 9000,
            'dob' => '1993-04-07', 'gender' => 'female', 'marital' => 'divorced', 'spouse' => null, 'children' => 1,
            'promotion' => ['date' => '2024-10-01', 'position' => 'Graphic Designer', 'level' => 'Mid', 'career' => 'Individual Contributor', 'band' => 'Band 2', 'zone' => 'Zone C', 'salary' => 42000],
        ],
        'kevin.aguilar@peopleportal.test' => [
            'hire' => '2023-09-18', 'type' => 'contract', 'status' => 'Regular',
            'position' => 'Client Support Specialist', 'level' => 'Mid', 'career' => 'Individual Contributor', 'band' => 'Band 2', 'zone' => 'Zone D', 'manager' => false,
            'salary' => 47000, 'allowances' => 6000, 'bonuses' => 7000,
            'dob' => '1994-08-16', 'gender' => 'male', 'marital' => 'single', 'spouse' => null, 'children' => 0,
        ],
        'liza.domingo@peopleportal.test' => [
            'hire' => '2021-07-05', 'type' => 'consultant', 'status' => 'Regular',
            'position' => 'Finance Analyst', 'level' => 'Senior', 'career' => 'Individual Contributor', 'band' => 'Band 4', 'zone' => 'Zone B', 'manager' => false,
            'salary' => 65000, 'allowances' => 5000, 'bonuses' => 13000,
            'dob' => '1988-12-01', 'gender' => 'female', 'marital' => 'widowed', 'spouse' => null, 'children' => 2,
        ],
        'miguel.pascual@peopleportal.test' => [
            'hire' => '2022-03-07', 'type' => 'full_time', 'status' => 'Turnover',
            'position' => 'Sales Executive', 'level' => 'Mid', 'career' => 'Individual Contributor', 'band' => 'Band 3', 'zone' => 'Zone C', 'manager' => false,
            'salary' => 58000, 'allowances' => 8000, 'bonuses' => 20000,
            'dob' => '1992-02-24', 'gender' => 'male', 'marital' => 'married', 'spouse' => 'Rowena Pascual', 'children' => 2,
            'resigned' => ['date' => '2026-06-30', 'reason' => 'Career shift to another industry', 'notes' => 'Served the full 30-day notice period. Cleared by Finance and IT.'],
        ],
        'nadine.ocampo@peopleportal.test' => [
            'hire' => '2026-06-01', 'type' => 'intern', 'status' => 'Probationary',
            'position' => 'Software Engineer Intern', 'level' => 'Intern', 'career' => 'Intern', 'band' => 'Band 1', 'zone' => 'Zone D', 'manager' => false,
            'salary' => 12000, 'allowances' => 2000, 'bonuses' => null,
            'dob' => '2004-07-14', 'gender' => 'female', 'marital' => 'single', 'spouse' => null, 'children' => 0,
        ],
    ];

    private const CITIES = [
        'Quezon City', 'Makati City', 'Pasig City', 'Cebu City', 'Davao City',
        'Taguig City', 'Mandaluyong City', 'Iloilo City', 'Bacolod City', 'Antipolo City',
    ];

    private const RELATIONSHIPS = ['Spouse', 'Mother', 'Father', 'Sibling', 'Guardian'];

    public function run(): void
    {
        $hr = User::where('email', 'hr@peopleportal.test')->first();
        $admin = User::where('email', 'admin@peopleportal.test')->first();
        $seeded = 0;

        foreach (self::PROFILES as $email => $profile) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->command->warn("Skipped profile for {$email} -- user not found.");
                continue;
            }

            $hireDate = Carbon::parse($profile['hire']);
            $resigned = $profile['resigned'] ?? null;

            $this->seedEmployee($user, $profile, $hireDate, $resigned, $hr);
            $this->seedPersonalInformation($user, $profile);
            $this->seedJobInformation($user, $profile, $hireDate);
            $this->seedSalaryInformation($user, $profile, $hireDate, $hr ?? $admin);
            $this->seedEmploymentHistory($user, $profile, $hireDate, $resigned, $hr ?? $admin);

            $seeded++;
        }

        $this->command->info("{$seeded} employee profiles seeded (employees, personal, job, salary, history).");
    }

    private function seedEmployee(User $user, array $profile, Carbon $hireDate, ?array $resigned, ?User $hr): void
    {
        $isRegular = $profile['status'] === 'Regular';

        Employee::updateOrCreate(
            ['user_id' => $user->id],
            [
                'employee_id' => 'D' . $hireDate->year . '-' . str_pad((string) $user->id, 4, '0', STR_PAD_LEFT),
                'hire_date' => $hireDate,
                'regularization_date' => $isRegular ? $hireDate->copy()->addMonths(6) : null,
                'employee_status' => $resigned ? 'resigned' : 'active',
                'employment_status' => $profile['status'],
                'employment_type' => $profile['type'],
                'termination_reason' => $resigned['reason'] ?? null,
                'termination_notes' => $resigned['notes'] ?? null,
                'terminated_by' => $resigned ? $hr?->id : null,
            ]
        );
    }

    private function seedPersonalInformation(User $user, array $profile): void
    {
        $city = self::CITIES[$user->id % count(self::CITIES)];
        $suffix = str_pad((string) $user->id, 3, '0', STR_PAD_LEFT);

        PersonalInformation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'date_of_birth' => $profile['dob'],
                'gender' => $profile['gender'],
                'marital_status' => $profile['marital'],
                'spouse_name' => $profile['spouse'],
                'num_children' => $profile['children'],
                'phone_number' => '+63917' . str_pad((string) (1000000 + $user->id * 4321), 7, '0', STR_PAD_LEFT),
                'alternate_phone_number' => '+63928' . str_pad((string) (2000000 + $user->id * 1234), 7, '0', STR_PAD_LEFT),
                'permanent_address' => "{$user->id} Sampaguita St., Barangay San Roque, {$city}, Philippines",
                'current_address' => "Unit {$user->id}B Horizon Residences, {$city}, Philippines",
                'emergency_contact_name' => $profile['spouse'] ?? explode(' ', $user->name)[0] . ' Family Contact',
                'emergency_contact_number' => '+63919' . str_pad((string) (3000000 + $user->id * 2468), 7, '0', STR_PAD_LEFT),
                'emergency_contact_relationship' => $profile['spouse']
                    ? 'Spouse'
                    : self::RELATIONSHIPS[$user->id % count(self::RELATIONSHIPS)],
                'tin' => '123-456-' . $suffix . '-000',
                'sss' => '34-' . str_pad((string) (1000000 + $user->id), 7, '0', STR_PAD_LEFT) . '-1',
                'philhealth' => '12-' . str_pad((string) (100000000 + $user->id), 9, '0', STR_PAD_LEFT) . '-3',
                'pagibig' => '1234-' . str_pad((string) (5000 + $user->id), 4, '0', STR_PAD_LEFT) . '-' . $suffix . '0',
            ]
        );
    }

    private function seedJobInformation(User $user, array $profile, Carbon $hireDate): void
    {
        $promotion = $profile['promotion'] ?? null;

        // The role held before the promotion, back-dated so latest() still
        // resolves the current position below.
        if ($promotion) {
            $previous = JobInformation::firstOrCreate(
                ['user_id' => $user->id, 'position_name' => $promotion['position']],
                [
                    'position_level' => $promotion['level'],
                    'career_level' => $promotion['career'],
                    'career_band' => $promotion['band'],
                    'career_zone' => $promotion['zone'],
                    'manager_id' => $user->immediate_sup_id,
                    'is_manager' => false,
                ]
            );

            $previous->created_at = $hireDate;
            $previous->updated_at = Carbon::parse($promotion['date']);
            $previous->saveQuietly();
        }

        $current = JobInformation::firstOrCreate(
            ['user_id' => $user->id, 'position_name' => $profile['position']],
            [
                'position_level' => $profile['level'],
                'career_level' => $profile['career'],
                'career_band' => $profile['band'],
                'career_zone' => $profile['zone'],
                'manager_id' => $user->immediate_sup_id,
                'is_manager' => $profile['manager'],
            ]
        );

        $current->created_at = $promotion ? Carbon::parse($promotion['date']) : $hireDate;
        $current->updated_at = $current->created_at;
        $current->saveQuietly();
    }

    private function seedSalaryInformation(User $user, array $profile, Carbon $hireDate, ?User $approver): void
    {
        $promotion = $profile['promotion'] ?? null;

        if ($promotion) {
            $previous = SalaryInformation::firstOrCreate(
                ['user_id' => $user->id, 'salary' => $promotion['salary']],
                [
                    'currency' => 'PHP',
                    'pay_frequency' => 'monthly',
                    'allowances' => 3000,
                    'bonuses' => null,
                    'approved_by' => $approver?->id,
                    'is_archived' => true,
                ]
            );

            $previous->created_at = $hireDate;
            $previous->updated_at = Carbon::parse($promotion['date']);
            $previous->saveQuietly();
        }

        $current = SalaryInformation::firstOrCreate(
            ['user_id' => $user->id, 'salary' => $profile['salary']],
            [
                'currency' => 'PHP',
                'pay_frequency' => $profile['type'] === 'intern' ? 'bi-weekly' : 'monthly',
                'allowances' => $profile['allowances'],
                'bonuses' => $profile['bonuses'],
                'approved_by' => $approver?->id,
                'is_archived' => false,
            ]
        );

        $current->created_at = $promotion ? Carbon::parse($promotion['date']) : $hireDate;
        $current->updated_at = $current->created_at;
        $current->saveQuietly();
    }

    private function seedEmploymentHistory(User $user, array $profile, Carbon $hireDate, ?array $resigned, ?User $author): void
    {
        $entries = [[
            'change_type' => 'hire',
            'from_value' => null,
            'to_value' => $profile['position'],
            'description' => "Hired as {$profile['position']} ({$profile['type']}).",
            'effective_date' => $hireDate->toDateString(),
        ]];

        if ($profile['status'] === 'Regular') {
            $entries[] = [
                'change_type' => 'status_change',
                'from_value' => 'Probationary',
                'to_value' => 'Regular',
                'description' => 'Passed probation and was regularized after six months.',
                'effective_date' => $hireDate->copy()->addMonths(6)->toDateString(),
            ];
        }

        if ($promotion = ($profile['promotion'] ?? null)) {
            $entries[] = [
                'change_type' => 'promotion',
                'from_value' => $promotion['position'],
                'to_value' => $profile['position'],
                'description' => "Promoted from {$promotion['position']} to {$profile['position']}.",
                'effective_date' => $promotion['date'],
            ];

            $entries[] = [
                'change_type' => 'salary_change',
                'from_value' => (string) $promotion['salary'],
                'to_value' => (string) $profile['salary'],
                'description' => 'Salary adjusted to match the new position.',
                'effective_date' => $promotion['date'],
            ];
        }

        if ($resigned) {
            $entries[] = [
                'change_type' => 'resignation',
                'from_value' => 'active',
                'to_value' => 'resigned',
                'description' => $resigned['reason'],
                'effective_date' => $resigned['date'],
            ];
        }

        foreach ($entries as $entry) {
            EmploymentHistory::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'change_type' => $entry['change_type'],
                    'effective_date' => $entry['effective_date'],
                ],
                array_merge($entry, ['created_by' => $author?->id])
            );
        }
    }
}
