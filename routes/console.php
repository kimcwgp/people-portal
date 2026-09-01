<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Apply shift changes on the 1st day of every month at midnight
Schedule::command('shifts:apply-changes')->monthlyOn(1, '00:00');

// Auto timeout day shift users at 10 PM sharp
Schedule::command('attendance:auto-timeout')
    ->dailyAt('22:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping();

// Auto-approve pending leaves and overtime after 3 days - runs daily at 9 AM
Schedule::command('requests:auto-approve')
    ->dailyAt('09:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping();

// Process year-end carryover on January 1st at 1 AM (carries over max 5 VL to next year, resets SL to 0)
Schedule::command('leave:process-year-end-carryover')
    ->cron('0 1 1 1 *') // At 1:00 AM on January 1st
    ->timezone('Asia/Manila')
    ->withoutOverlapping();

// Accrue monthly leave credits (1.25 VL and 1.25 SL per month) on the 1st of every month at 2 AM
Schedule::command('leave:accrue-monthly')
    ->monthlyOn(1, '02:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping();
