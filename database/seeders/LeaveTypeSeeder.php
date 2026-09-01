<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Vacation Leave',
                'type' => 'VL'
            ],
            [
                'name' => 'Sick Leave (WITH Medical Certificate)',
                'type' => 'SL'
            ],
            [
                'name' => 'Bereavement Leave',
                'type' => 'VL'
            ],
            [
                'name' => 'Emergency Leave',
                'type' => 'EL'
            ],
            [
                'name' => 'Maternity Leave',
                'type' => 'ML'
            ],
            [
                'name' => 'Paternity Leave',
                'type' => 'PL'
            ],
            [
                'name' => 'Birthday Leave (only applicable to associates with at least 1 year tenure)',
                'type' => 'BL'
            ],
            [
                'name' => 'Sick Leave (WITHOUT Medical Certificate)',
                'type' => 'SL'
            ],
            [
                'name' => 'SL - Leave without Pay (for interns/probee/part-timers)',
                'type' => 'SL'
            ],
            [
                'name' => 'VLEL - Leave without Pay (for interns/probee/part-timers)',
                'type' => 'LWOP'
            ],
            [
                'name' => 'Other',
                'type' => 'VL'
            ]
        ];

        foreach ($leaveTypes as $leaveType) {
            LeaveType::create($leaveType);
        }
    }
}