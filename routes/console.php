<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

// Schedule::call(function () {
//     Log::info('Cron job is working at ' . now());
// })->everyMinute()->name('cron-test');

// // Appointment reminder notifications - runs every minute to catch all intervals
// Schedule::command('appointments:send-reminders')
//     ->everyMinute()
//     ->withoutOverlapping()
//     ->runInBackground()
//     ->before(function () {
//         Log::info('Appointment reminders command starting at ' . now());
//     })
//     ->onSuccess(function () {
//         Log::info('Appointment reminders command completed successfully at ' . now());
//     })
//     ->onFailure(function () {
//         Log::error('Appointment reminders command failed at ' . now());
//     })
//     ->name('appointment-reminders');

// // Keep the original method as backup
// Schedule::command('timeslots:cleanup-expired')
//     ->everyMinute()
//     ->withoutOverlapping()
//     ->runInBackground()
//     ->before(function () {
//         Log::info('Scheduled timeslots:send command is about to run at ' . now());
//     })
//     ->onSuccess(function () {
//         Log::info('Scheduled timeslots:send command completed successfully at ' . now());
//     })
//     ->onFailure(function () {
//         Log::error('Scheduled timeslots:send command failed at ' . now());
//     })->name('timeslots');

// // Test scheduler functionality
// Schedule::call(function () {
//     Log::info('Test scheduled task executed at ' . now());
// })->everyMinute()->name('test-scheduler');

Schedule::command('notify:subscriptions 30')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->before(function () {
        Log::info('Scheduled notify:subscriptions 30 is about to run at '.now());
        dd();
    })
    ->onSuccess(function () {
        Log::info('Scheduled notify:subscriptions 30 command completed successfully at '.now());
    })
    ->onFailure(function () {
        Log::error('Scheduled notify:subscriptions 30 command failed at '.now());
    })->name('subscription30');

Schedule::command('notify:subscriptions 15')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->runInBackground()
    ->before(function () {
        Log::info('Scheduled notify:subscriptions 15 is about to run at '.now());
    })
    ->onSuccess(function () {
        Log::info('Scheduled notify:subscriptions 15 command completed successfully at '.now());
    })
    ->onFailure(function () {
        Log::error('Scheduled notify:subscriptions 15 command failed at '.now());
    })->name('subscription15');

Schedule::command('notify:subscriptions 7')
    ->dailyAt('00:10')
    ->withoutOverlapping()
    ->runInBackground()
    ->before(function () {
        Log::info('Scheduled notify:subscriptions 7 is about to run at '.now());
    })
    ->onSuccess(function () {
        Log::info('Scheduled notify:subscriptions 7 command completed successfully at '.now());
    })
    ->onFailure(function () {
        Log::error('Scheduled notify:subscriptions 7 command failed at '.now());
    })->name('subscription10');

Schedule::command('notify:subscriptions 1')
    ->dailyAt('00:15')
    ->withoutOverlapping()
    ->runInBackground()
    ->before(function () {
        Log::info('Scheduled notify:subscriptions 1 is about to run at '.now());
    })
    ->onSuccess(function () {
        Log::info('Scheduled notify:subscriptions 1 command completed successfully at '.now());
    })
    ->onFailure(function () {
        Log::error('Scheduled notify:subscriptions 1 command failed at '.now());
    })->name('subscription1');

Schedule::command('notify:subscriptions 0')
    ->dailyAt('00:20')
    ->withoutOverlapping()
    ->runInBackground()
    ->before(function () {
        Log::info('Scheduled notify:subscriptions 0 is about to run at '.now());
    })
    ->onSuccess(function () {
        Log::info('Scheduled notify:subscriptions 0 command completed successfully at '.now());
    })
    ->onFailure(function () {
        Log::error('Scheduled notify:subscriptions 0 command failed at '.now());
    })->name('subscription0');
