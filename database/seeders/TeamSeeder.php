<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Team;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $teams = [
            'Mancomm',
            'Human Resource',
            'Administration',
            'Finance',
            'Production',
            'Project Management',
            'Development',
            'Quality Assurance',
            'Creatives',
            'Digital Marketing',
            'Sales',
            'Interns',
            'Client Support',
        ];

        foreach ($teams as $team) {
            Team::firstOrCreate([
                'name' => $team,
                'description' => "{$team} Department",
            ]);
        }
    }
}
