<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

Schedule::call(function () {
    Log::info('Cron job is working at ' . now());
})->everyMinute()->name('cron-test');


// Appointment reminder notifications - runs every minute to catch all intervals
Schedule::command('appointments:send-reminders')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->before(function () {
        Log::info('Appointment reminders command starting at ' . now());
    })
    ->onSuccess(function () {
        Log::info('Appointment reminders command completed successfully at ' . now());
    })
    ->onFailure(function () {
        Log::error('Appointment reminders command failed at ' . now());
    })
    ->name('appointment-reminders');

// Keep the original method as backup
Schedule::command('timeslots:cleanup-expired')
    ->everyMinute()  
    ->withoutOverlapping()
    ->runInBackground()
    ->before(function () {
        Log::info('Scheduled timeslots:send command is about to run at ' . now());
    })
    ->onSuccess(function () {
        Log::info('Scheduled timeslots:send command completed successfully at ' . now());
    })
    ->onFailure(function () {
        Log::error('Scheduled timeslots:send command failed at ' . now());
    })->name('timeslots');

// Test scheduler functionality
Schedule::call(function () {
    Log::info('Test scheduled task executed at ' . now());
})->everyMinute()->name('test-scheduler');
