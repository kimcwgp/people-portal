<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateEmployeeIdFormat extends Command
{
    protected $signature = 'employees:update-id-format';
    protected $description = 'Update employee IDs from EMP#### to D{YEAR}-#### format';

    public function handle()
    {
        $employees = Employee::where('employee_id', 'LIKE', 'EMP%')->get();
        
        if ($employees->isEmpty()) {
            $this->info('No employees found with old format.');
            return 0;
        }

        $this->info("Found {$employees->count()} employees with old format");
        
        foreach ($employees as $emp) {
            $year = Carbon::parse($emp->hire_date)->year;
            $newId = 'D' . $year . '-' . str_pad($emp->user_id, 4, '0', STR_PAD_LEFT);
            
            $this->line("Updating User {$emp->user_id}: {$emp->employee_id} -> {$newId}");
            
            $emp->employee_id = $newId;
            $emp->save();
        }
        
        $this->info('Done!');
        return 0;
    }
}
