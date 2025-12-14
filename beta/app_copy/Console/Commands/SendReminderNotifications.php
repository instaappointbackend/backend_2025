<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendReminderNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reminders:send {--test : Run in test mode without sending actual notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Send push notifications for due reminders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('SendReminderNotifications: Handle method started at ' . now());

        try {
            // Get NotificationService from container instead of constructor injection
            $notificationService = app(NotificationService::class);
            Log::info('SendReminderNotifications: NotificationService resolved successfully');

            $this->info('Starting reminder notification process...');
            Log::info('SendReminderNotifications: Process starting');

            // Safely get the test option (might not be available when called via scheduler)
            $testMode = false;
            try {
                $testMode = $this->option('test');
            } catch (\Exception $e) {
                Log::info('SendReminderNotifications: Test option not available (normal for scheduled execution)');
            }
            Log::info('SendReminderNotifications: Test mode = ' . ($testMode ? 'true' : 'false'));

            if ($testMode) {
                $this->warn('Running in TEST MODE - No actual notifications will be sent');
                Log::info('SendReminderNotifications: Running in test mode');
            }

            // Get current time
            $now = Carbon::now();
            $currentDateTime = $now->format('Y-m-d H:i:s');

            Log::info("SendReminderNotifications: Current datetime = {$currentDateTime}");

            // Find reminders that are due (handles both date and datetime formats)
            $dueReminders = Reminder::whereRaw("CONCAT(DATE(reminder_date), ' ', reminder_time) <= ?", [$currentDateTime])
                ->where('status', 'pending')
                ->where('notification_sent', false)
                ->with(['user', 'customer', 'appointment'])
                ->get();

            Log::info("SendReminderNotifications: Found {$dueReminders->count()} due reminders");
            $this->info("Found {$dueReminders->count()} due reminders");

            $successCount = 0;
            $errorCount = 0;

            foreach ($dueReminders as $reminder) {
                Log::info("SendReminderNotifications: Processing reminder ID {$reminder->id}");

                try {
                    if ($testMode) {
                        $this->line("TEST: Would send notification for reminder ID {$reminder->id} to user {$reminder->user_id}");
                        Log::info("SendReminderNotifications: TEST - Would send notification for reminder ID {$reminder->id}");
                        $successCount++;
                        continue;
                    }

                    // Send notification
                    Log::info("SendReminderNotifications: Sending notification for reminder ID {$reminder->id}");
                    $this->sendReminderNotification($reminder, $notificationService);

                    // Mark as sent
                    $reminder->update([
                        'notification_sent' => true,
                        'is_read' => false // Reset read status for new notification
                    ]);

                    $successCount++;
                    $this->info("✓ Sent notification for reminder: {$reminder->title}");
                    Log::info("SendReminderNotifications: Successfully sent notification for reminder: {$reminder->title}");

                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMessage = "Failed to send notification for reminder ID {$reminder->id}: " . $e->getMessage();
                    $this->error("✗ " . $errorMessage);

                    Log::error('SendReminderNotifications: Notification failed', [
                        'reminder_id' => $reminder->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Handle recurring reminders
            if (!$testMode) {
                Log::info('SendReminderNotifications: Handling recurring reminders');
                $this->handleRecurringReminders();
            }

            $this->info("Reminder notification process completed!");
            $this->info("✓ Success: {$successCount}");

            Log::info("SendReminderNotifications: Process completed - Success: {$successCount}, Errors: {$errorCount}");

            if ($errorCount > 0) {
                $this->error("✗ Errors: {$errorCount}");
            }

            Log::info('SendReminderNotifications: Handle method completed successfully');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('SendReminderNotifications: Fatal error in handle method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->error('Fatal error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Send push notification for a reminder
     */
    private function sendReminderNotification(Reminder $reminder, NotificationService $notificationService)
    {
        Log::info("SendReminderNotifications: Preparing notification for reminder ID {$reminder->id}", [
            'reminder_date' => $reminder->reminder_date,
            'reminder_time' => $reminder->reminder_time,
            'reminder_date_type' => gettype($reminder->reminder_date),
            'reminder_time_type' => gettype($reminder->reminder_time)
        ]);

        $title = 'Reminder: ' . $reminder->title;
        $body = $reminder->description ?: "You have a {$reminder->priority} priority reminder.";

        // Add time context to body - with robust parsing
        try {
            // Get raw date value to avoid Carbon casting issues
            $rawDate = $reminder->getRawOriginal('reminder_date') ?? $reminder->reminder_date;
            $rawTime = $reminder->reminder_time;

            Log::info("SendReminderNotifications: Raw datetime values", [
                'raw_date' => $rawDate,
                'raw_time' => $rawTime,
                'raw_date_type' => gettype($rawDate),
                'raw_time_type' => gettype($rawTime)
            ]);

            // Extract just the date part (handle both date and datetime formats)
            if (is_string($rawDate)) {
                // If it's a string, extract just the date part
                $dateOnly = substr($rawDate, 0, 10); // Get YYYY-MM-DD part
            } else {
                // If it's a Carbon object, format it
                $dateOnly = Carbon::parse($rawDate)->format('Y-m-d');
            }

            // Clean and format time
            $timeOnly = trim($rawTime);
            if (strlen($timeOnly) == 5) { // H:i format, add seconds
                $timeOnly .= ':00';
            }

            Log::info("SendReminderNotifications: Cleaned datetime values", [
                'date_only' => $dateOnly,
                'time_only' => $timeOnly,
                'combined' => $dateOnly . ' ' . $timeOnly
            ]);

            $reminderDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateOnly . ' ' . $timeOnly);
            $body .= " (Scheduled for {$reminderDateTime->format('M j, Y \a\t g:i A')})";

        } catch (\Exception $e) {
            Log::error("SendReminderNotifications: DateTime parsing failed", [
                'reminder_id' => $reminder->id,
                'reminder_date' => $reminder->reminder_date,
                'reminder_time' => $reminder->reminder_time,
                'raw_date' => $reminder->getRawOriginal('reminder_date'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Fallback - just show date and time without formatting
            $body .= " (Reminder scheduled)";
        }

        $data = [
            'type' => 'reminder_notification',
            'reminderId' => $reminder->id,
            'reminder_date' => $reminder->reminder_date,
            'reminder_time' => $reminder->reminder_time,
            'priority' => $reminder->priority,
            'reminder_type' => $reminder->type,
            'screenName' => 'ReminderDetailScreen'
        ];

        // Add target-specific data if available
        if ($reminder->target_type && $reminder->target_id) {
            $data['target_type'] = $reminder->target_type;
            $data['target_id'] = $reminder->target_id;

            if ($reminder->target_type === 'appointment' && $reminder->appointment) {
                $data['appointment_id'] = $reminder->appointment->id;
                $data['screenName'] = 'AppointmentDetails';
            } elseif ($reminder->target_type === 'customer' && $reminder->customer) {
                $data['customer_id'] = $reminder->customer->id;
                $data['customer_name'] = $reminder->customer->name;
                $data['screenName'] = 'CustomerDetails';
            }
        }

        Log::info("SendReminderNotifications: Calling notification service", [
            'user_id' => $reminder->user_id,
            'title' => $title,
            'body' => $body
        ]);

        // Send the notification
        $notificationService->sendPushNotification(
            $reminder->user_id,
            $title,
            $body,
            $data
        );

        Log::info("SendReminderNotifications: Notification service call completed for reminder ID {$reminder->id}");
    }

    /**
     * Handle recurring reminders by creating the next occurrence
     */
    private function handleRecurringReminders()
    {
        Log::info('SendReminderNotifications: Starting recurring reminders processing');

        $recurringReminders = Reminder::where('type', 'recurring')
            ->where('status', 'pending')
            ->where('notification_sent', true)
            ->whereDate('reminder_date', '<=', now())
            ->get();

        Log::info("SendReminderNotifications: Found {$recurringReminders->count()} recurring reminders to process");

        foreach ($recurringReminders as $reminder) {
            try {
                Log::info("SendReminderNotifications: Creating next occurrence for recurring reminder ID {$reminder->id}");
                $this->createNextRecurringReminder($reminder);
            } catch (\Exception $e) {
                Log::error('SendReminderNotifications: Failed to create next recurring reminder', [
                    'reminder_id' => $reminder->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Create the next occurrence of a recurring reminder
     */
    private function createNextRecurringReminder(Reminder $reminder)
    {
        // Handle both date-only and datetime formats
        $dateOnly = $reminder->reminder_date instanceof Carbon
            ? $reminder->reminder_date->format('Y-m-d')
            : Carbon::parse($reminder->reminder_date)->format('Y-m-d');

        $currentDate = Carbon::createFromFormat('Y-m-d', $dateOnly);
        $nextDate = null;

        switch ($reminder->recurrence_pattern) {
            case 'daily':
                $nextDate = $currentDate->addDay();
                break;
            case 'weekly':
                $nextDate = $currentDate->addWeek();
                break;
            case 'monthly':
                $nextDate = $currentDate->addMonth();
                break;
            default:
                Log::warning("SendReminderNotifications: Unknown recurrence pattern: {$reminder->recurrence_pattern}");
                return; // Unknown pattern
        }

        // Check if we've reached the recurrence end date
        if ($reminder->recurrence_end_date && $nextDate->gt(Carbon::parse($reminder->recurrence_end_date))) {
            // Mark the original reminder as completed since recurrence has ended
            $reminder->update(['status' => 'completed']);
            Log::info("SendReminderNotifications: Recurring reminder ID {$reminder->id} reached end date, marked as completed");
            return;
        }

        // Create the next occurrence
        $nextReminder = $reminder->replicate();
        $nextReminder->reminder_date = $nextDate->format('Y-m-d');
        $nextReminder->notification_sent = false;
        $nextReminder->is_read = false;
        $nextReminder->created_at = now();
        $nextReminder->updated_at = now();
        $nextReminder->save();

        // Mark the current reminder as completed
        $reminder->update(['status' => 'completed']);

        $message = "Created next recurring reminder for: {$reminder->title} on {$nextDate->format('Y-m-d')}";
        $this->info($message);
        Log::info("SendReminderNotifications: " . $message);
    }
}
