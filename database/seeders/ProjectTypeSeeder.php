<?php

namespace Database\Seeders;

use App\Models\ProjectType;
use Illuminate\Database\Seeder;

class ProjectTypeSeeder extends Seeder
{
    private const TYPES = [
        ['name' => 'Web Application',      'description' => 'Browser-based product work: new builds, rewrites and major feature streams.'],
        ['name' => 'Mobile Application',   'description' => 'iOS and Android delivery, including cross-platform builds.'],
        ['name' => 'Maintenance & Support', 'description' => 'Bug fixes, dependency upgrades and production support retainers.'],
        ['name' => 'Data & Analytics',     'description' => 'Pipelines, warehousing, reporting and dashboard engagements.'],
        ['name' => 'Systems Integration',  'description' => 'Connecting third-party or legacy systems through APIs and middleware.'],
        ['name' => 'UI/UX Design',         'description' => 'Research, wireframes, visual design and design-system work.'],
        ['name' => 'Quality Assurance',    'description' => 'Dedicated manual and automated testing engagements.'],
        ['name' => 'DevOps & Cloud',       'description' => 'Infrastructure, CI/CD pipelines and cloud migration work.'],
        ['name' => 'Internal Tooling',     'description' => 'In-house systems built for the company rather than a client.'],
        ['name' => 'Discovery & Consulting', 'description' => 'Short scoping engagements, audits and technical due diligence.'],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            ProjectType::firstOrCreate(['name' => $type['name']], ['description' => $type['description']]);
        }

        $this->command->info(count(self::TYPES) . ' project types seeded.');
    }
}
