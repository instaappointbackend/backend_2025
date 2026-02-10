<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Appointment Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the automated appointment
    | cleanup system that removes expired and abandoned appointments.
    |
    */

    'enabled' => env('APPOINTMENT_CLEANUP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cleanup Schedule
    |--------------------------------------------------------------------------
    |
    | How often the cleanup job should run. Uses cron expression format.
    | Default: Every 15 minutes
    |
    */
    'schedule' => env('APPOINTMENT_CLEANUP_SCHEDULE', '*/15 * * * *'),

    /*
    |--------------------------------------------------------------------------
    | Cleanup Criteria
    |--------------------------------------------------------------------------
    |
    | Define the timeout periods for different appointment states.
    | Times are specified in the units indicated.
    |
    */
    'criteria' => [
        // Draft appointments timeout (minutes)
        'draft_timeout_minutes' => env('DRAFT_TIMEOUT_MINUTES', 30),

        // Payment pending appointments timeout (minutes)
        'payment_pending_timeout_minutes' => env('PAYMENT_PENDING_TIMEOUT_MINUTES', 15),

        // Failed payment appointments cleanup (hours)
        'failed_payment_cleanup_hours' => env('FAILED_PAYMENT_CLEANUP_HOURS', 24),

        // Old cancelled appointments cleanup (days)
        'cancelled_cleanup_days' => env('CANCELLED_CLEANUP_DAYS', 7),

        // Expired time slot cleanup (minutes)
        'expired_timeslot_cleanup_minutes' => env('EXPIRED_TIMESLOT_CLEANUP_MINUTES', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety Limits
    |--------------------------------------------------------------------------
    |
    | Safety mechanisms to prevent excessive deletions or resource usage.
    |
    */
    'limits' => [
        // Maximum appointments to delete in a single run
        'max_deletions_per_run' => env('MAX_DELETIONS_PER_RUN', 100),

        // Maximum execution time in seconds
        'max_execution_time_seconds' => env('MAX_CLEANUP_EXECUTION_TIME', 300),

        // Batch size for processing large datasets
        'batch_size' => env('CLEANUP_BATCH_SIZE', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how cleanup operations are logged.
    |
    */
    'logging' => [
        'enabled' => env('CLEANUP_LOGGING_ENABLED', true),
        'level' => env('CLEANUP_LOG_LEVEL', 'info'),
        'channel' => env('CLEANUP_LOG_CHANNEL', 'daily'),
        'detailed_logging' => env('CLEANUP_DETAILED_LOGGING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure notifications for cleanup operations.
    |
    */
    'notifications' => [
        'enabled' => env('CLEANUP_NOTIFICATIONS_ENABLED', false),
        'email_on_errors' => env('CLEANUP_EMAIL_ON_ERRORS', false),
        'admin_email' => env('CLEANUP_ADMIN_EMAIL', null),
        'error_threshold' => env('CLEANUP_ERROR_THRESHOLD', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Actions
    |--------------------------------------------------------------------------
    |
    | Define which cleanup actions should be performed.
    |
    */
    'actions' => [
        'cleanup_draft_appointments' => env('CLEANUP_DRAFT_APPOINTMENTS', true),
        'cleanup_payment_pending' => env('CLEANUP_PAYMENT_PENDING', true),
        'cleanup_failed_payments' => env('CLEANUP_FAILED_PAYMENTS', true),
        'cleanup_old_cancelled' => env('CLEANUP_OLD_CANCELLED', true),
        'cleanup_expired_timeslots' => env('CLEANUP_EXPIRED_TIMESLOTS', true),
        'cleanup_orphaned_payments' => env('CLEANUP_ORPHANED_PAYMENTS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to development and testing environments.
    |
    */
    'development' => [
        'dry_run_default' => env('CLEANUP_DRY_RUN_DEFAULT', false),
        'verbose_output' => env('CLEANUP_VERBOSE_OUTPUT', false),
        'preserve_test_data' => env('CLEANUP_PRESERVE_TEST_DATA', false),
    ],
];
