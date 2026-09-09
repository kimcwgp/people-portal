<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    private const PROJECTS = [
        ['name' => 'Northwind Fleet Tracker',        'client' => 'Northwind Logistics',      'type' => 'Web Application',        'pm' => 'carlo.mendoza@peopleportal.test', 'description' => 'Live GPS tracking and delivery ETA dashboard for dispatchers.'],
        ['name' => 'Northwind Driver App',           'client' => 'Northwind Logistics',      'type' => 'Mobile Application',     'pm' => 'manager@peopleportal.test',       'description' => 'Android app for proof-of-delivery capture and route handover.'],
        ['name' => 'Bayanihan Loan Origination',     'client' => 'Bayanihan Microfinance',   'type' => 'Web Application',        'pm' => 'carlo.mendoza@peopleportal.test', 'description' => 'End-to-end loan application, credit scoring and disbursement workflow.'],
        ['name' => 'Harborline Claims Intake',       'client' => 'Harborline Insurance',     'type' => 'Systems Integration',    'pm' => 'manager@peopleportal.test',       'description' => 'Claims intake API bridging the broker portal and the legacy AS/400 core.'],
        ['name' => 'Verdant Yield Analytics',        'client' => 'Verdant Agritech',         'type' => 'Data & Analytics',       'pm' => 'carlo.mendoza@peopleportal.test', 'description' => 'Sensor ingestion pipeline plus yield and irrigation dashboards.'],
        ['name' => 'Cascade Patient Portal',         'client' => 'Cascade Health Partners',  'type' => 'Web Application',        'pm' => 'enrico.santos@peopleportal.test', 'description' => 'Appointment booking and secure records access for patients.'],
        ['name' => 'Lumina Storefront Revamp',       'client' => 'Lumina Retail Group',      'type' => 'UI/UX Design',           'pm' => 'carlo.mendoza@peopleportal.test', 'description' => 'Design-system rebuild and checkout redesign for the storefront.'],
        ['name' => 'Lumina Loyalty Platform',        'client' => 'Lumina Retail Group',      'type' => 'Web Application',        'pm' => 'teamlead@peopleportal.test',      'description' => 'Points accrual, tiering and reward redemption services.'],
        ['name' => 'Ironbark Site Inspections',      'client' => 'Ironbark Construction',    'type' => 'Mobile Application',     'pm' => 'manager@peopleportal.test',       'description' => 'Offline-first inspection checklists with photo evidence sync.'],
        ['name' => 'Solstice Ad Ops Console',        'client' => 'Solstice Media',           'type' => 'Internal Tooling',       'pm' => 'enrico.santos@peopleportal.test', 'description' => 'Campaign trafficking and inventory forecasting console.'],
        ['name' => 'Pioneer Maintenance Desk',       'client' => 'Pioneer Energy Solutions', 'type' => 'Maintenance & Support',  'pm' => 'divina.reyes@peopleportal.test',  'description' => 'Support retainer covering the ticketing desk and quarterly upgrades.'],
        ['name' => 'Meridian Student Information',   'client' => 'Meridian Education Trust', 'type' => 'Web Application',        'pm' => 'carlo.mendoza@peopleportal.test', 'description' => 'Enrolment, grading and parent-communication modules.'],
        ['name' => 'Cascade QA Automation',          'client' => 'Cascade Health Partners',  'type' => 'Quality Assurance',      'pm' => 'divina.reyes@peopleportal.test',  'description' => 'Regression suite and CI test harness for the patient portal.'],
        ['name' => 'People Portal (Internal)',       'client' => null,                       'type' => 'Internal Tooling',       'pm' => 'manager@peopleportal.test',       'description' => 'This HR portal: attendance, leaves, overtime and standups.'],
    ];

    public function run(): void
    {
        $seeded = 0;

        foreach (self::PROJECTS as $data) {
            $type = ProjectType::where('name', $data['type'])->first();
            $pm = User::where('email', $data['pm'])->first();
            $client = $data['client'] ? Client::where('name', $data['client'])->first() : null;

            if (! $type || ! $pm) {
                $this->command->warn("Skipped project '{$data['name']}' -- missing project type or project manager.");
                continue;
            }

            $slug = strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $data['name']));

            Project::firstOrCreate(
                ['project_name' => $data['name']],
                [
                    'client_id' => $client?->id,
                    'project_type_id' => $type->id,
                    'pm_id' => $pm->id,
                    'clickup_url' => "https://app.clickup.com/9001/v/li/{$slug}",
                    'glip_url' => "https://app.glip.com/r/{$slug}",
                    'description' => $data['description'],
                ]
            );

            $seeded++;
        }

        $this->command->info("{$seeded} projects seeded.");
    }
}
