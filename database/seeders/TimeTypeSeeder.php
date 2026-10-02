<?php

namespace Database\Seeders;

use App\Models\TimeType;
use Illuminate\Database\Seeder;

class TimeTypeSeeder extends Seeder
{
    /** Tints are design-system status keys, not free-form colours. */
    private const TYPES = [
        ['name' => 'Work General Stuff', 'tint' => 'green',  'is_billable' => true,  'description' => 'Regular billable delivery work.'],
        ['name' => 'Scrum',              'tint' => 'orange', 'is_billable' => true,  'description' => 'Standups, planning, retros and refinement.'],
        ['name' => 'Admin - Internal',   'tint' => 'purple', 'is_billable' => false, 'description' => 'Internal administration not billed to a client.'],
        ['name' => 'Meeting',            'tint' => 'yellow', 'is_billable' => true,  'description' => 'Client and internal meetings.'],
        ['name' => 'Training',           'tint' => 'blue',   'is_billable' => false, 'description' => 'Learning, certification and onboarding.'],
        ['name' => 'Support',            'tint' => 'cyan',   'is_billable' => true,  'description' => 'Production support and incident handling.'],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $index => $type) {
            TimeType::firstOrCreate(
                ['name' => $type['name']],
                $type + ['sort_order' => $index + 1, 'is_active' => true]
            );
        }

        $this->command->info(count(self::TYPES) . ' time types seeded.');
    }
}
