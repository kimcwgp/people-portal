<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            1 => 'Team Lead',
            2 => 'Technical Consultant',
            3 => 'UI/UX',
            4 => 'Team Lead',
            5 => 'Technical Consultant',
            6 => 'Shopify Developer',
            7 => 'AI Automation Specialist',
        ];

        foreach ($positions as $userId => $positionName) {
            User::where('id', $userId)->update([
                'position_id' => \App\Models\Position::where('name', $positionName)->value('id'),
            ]);
        }
    }
}