<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectType;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    private const PROJECTS = [
        ['name' => 'Northwind Fleet Tracker',        'client' => 'Northwind Logistics',      'type' => 'Web Application', 'description' => 'Live GPS tracking and delivery ETA dashboard for dispatchers.'],
        ['name' => 'Northwind Driver App',           'client' => 'Northwind Logistics',      'type' => 'Mobile Application',       'description' => 'Android app for proof-of-delivery capture and route handover.'],
        ['name' => 'Bayanihan Loan Origination',     'client' => 'Bayanihan Microfinance',   'type' => 'Web Application', 'description' => 'End-to-end loan application, credit scoring and disbursement workflow.'],
        ['name' => 'Harborline Claims Intake',       'client' => 'Harborline Insurance',     'type' => 'Systems Integration',       'description' => 'Claims intake API bridging the broker portal and the legacy AS/400 core.'],
        ['name' => 'Verdant Yield Analytics',        'client' => 'Verdant Agritech',         'type' => 'Data & Analytics', 'description' => 'Sensor ingestion pipeline plus yield and irrigation dashboards.'],
        ['name' => 'Cascade Patient Portal',         'client' => 'Cascade Health Partners',  'type' => 'Web Application', 'description' => 'Appointment booking and secure records access for patients.'],
        ['name' => 'Lumina Storefront Revamp',       'client' => 'Lumina Retail Group',      'type' => 'UI/UX Design', 'description' => 'Design-system rebuild and checkout redesign for the storefront.'],
        ['name' => 'Lumina Loyalty Platform',        'client' => 'Lumina Retail Group',      'type' => 'Web Application',      'description' => 'Points accrual, tiering and reward redemption services.'],
        ['name' => 'Ironbark Site Inspections',      'client' => 'Ironbark Construction',    'type' => 'Mobile Application',       'description' => 'Offline-first inspection checklists with photo evidence sync.'],
        ['name' => 'Solstice Ad Ops Console',        'client' => 'Solstice Media',           'type' => 'Internal Tooling', 'description' => 'Campaign trafficking and inventory forecasting console.'],
        ['name' => 'Pioneer Maintenance Desk',       'client' => 'Pioneer Energy Solutions', 'type' => 'Maintenance & Support',  'description' => 'Support retainer covering the ticketing desk and quarterly upgrades.'],
        ['name' => 'Meridian Student Information',   'client' => 'Meridian Education Trust', 'type' => 'Web Application', 'description' => 'Enrolment, grading and parent-communication modules.'],
        ['name' => 'Cascade QA Automation',          'client' => 'Cascade Health Partners',  'type' => 'Quality Assurance',  'description' => 'Regression suite and CI test harness for the patient portal.'],
        ['name' => 'People Portal (Internal)',       'client' => null,                       'type' => 'Internal Tooling',       'description' => 'This HR portal: attendance, leaves, time entries and standups.'],
    ];

    public function run(): void
    {
        $seeded = 0;

        foreach (self::PROJECTS as $data) {
            $type = ProjectType::where('name', $data['type'])->first();
            $client = $data['client'] ? Client::where('name', $data['client'])->first() : null;

            if (! $type) {
                $this->command->warn("Skipped project '{$data['name']}' -- missing project type.");
                continue;
            }

            $slug = strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $data['name']));

            Project::firstOrCreate(
                ['project_name' => $data['name']],
                [
                    'client_id' => $client?->id,
                    'project_type_id' => $type->id,
                    'clickup_url' => "https://app.clickup.com/9001/v/li/{$slug}",
                    'description' => $data['description'],
                ]
            );

            $seeded++;
        }

        $this->command->info("{$seeded} projects seeded.");
    }
}
