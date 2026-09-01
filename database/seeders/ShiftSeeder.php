<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            // Day shifts
            ['shift_type' => 'day',   'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['shift_type' => 'day',   'start_time' => '09:00:00', 'end_time' => '18:00:00'],
            ['shift_type' => 'day',   'start_time' => '10:00:00', 'end_time' => '19:00:00'],

            // Night shifts (cross midnight)
            ['shift_type' => 'night', 'start_time' => '18:00:00', 'end_time' => '02:00:00'],
            ['shift_type' => 'night', 'start_time' => '19:00:00', 'end_time' => '03:00:00'],
            ['shift_type' => 'night', 'start_time' => '20:00:00', 'end_time' => '04:00:00'],
        ];

        foreach ($shifts as $data) {
            Shift::firstOrCreate($data);
        }
    }
}
