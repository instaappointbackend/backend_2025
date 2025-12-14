<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notification reminders for upcoming appointments';

    /**
     * Reminder intervals in minutes before appointment start
     */
    const REMINDER_INTERVALS = [
        1440, // 24 hours
        300,  // 5 hours
        60,   // 1 hour
        30,   // 30 minutes
        5,     // 5 minutes
        1,     // 1 minutes
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting appointment reminder notifications...');
        
        $totalSent = 0;
        
        foreach (self::REMINDER_INTERVALS as $minutes) {
            $sent = $this->sendRemindersForInterval($minutes);
            $totalSent += $sent;
            
            $hours = $minutes >= 60 ? ($minutes / 60) . ' hour(s)' : $minutes . ' minute(s)';
            $this->info("Sent {$sent} reminders for {$hours} before appointments");
        }
        
        $this->info("Total reminders sent: {$totalSent}");
        Log::info("Appointment reminders completed. Total sent: {$totalSent}");
        
        return 0;
    }

    /**
     * Send reminders for a specific time interval
     */
    private function sendRemindersForInterval(int $minutes): int
    {
        $targetTime = Carbon::now()->addMinutes($minutes);
        $startRange = $targetTime->copy()->subMinutes(2); // 2-minute window
        $endRange = $targetTime->copy()->addMinutes(2);
        
        // Get appointments that start within the target time range
        $appointments = Appointment::with(['client', 'provider', 'service', 'comboService'])
            ->whereIn('status', [
                Appointment::STATUS_CONFIRMED,
                Appointment::STATUS_PENDING
            ])
            ->whereDate('date', $targetTime->toDateString())
            ->whereTime('start_time', '>=', $startRange->format('H:i:s'))
            ->whereTime('start_time', '<=', $endRange->format('H:i:s'))
            ->get();

        $sentCount = 0;

        foreach ($appointments as $appointment) {
            try {
                $appointmentSentCount = 0;

                // Send reminder to client
                if ($appointment->client && !AppointmentReminder::wasReminderSent(
                    $appointment->id, 
                    $minutes, 
                    AppointmentReminder::RECIPIENT_CLIENT, 
                    $appointment->client->id
                )) {
                    $this->sendClientReminder($appointment, $minutes);
                    $appointmentSentCount++;
                }

                // Send reminder to provider
                if ($appointment->provider && !AppointmentReminder::wasReminderSent(
                    $appointment->id, 
                    $minutes, 
                    AppointmentReminder::RECIPIENT_PROVIDER, 
                    $appointment->provider->id
                )) {
                    $this->sendProviderReminder($appointment, $minutes);
                    $appointmentSentCount++;
                }
                
                $sentCount += $appointmentSentCount;
                
            } catch (\Exception $e) {
                Log::error("Failed to send reminder for appointment {$appointment->id}: " . $e->getMessage());
                $this->error("Failed to send reminder for appointment {$appointment->id}");
            }
        }

        return $sentCount;
    }

    /**
     * Send reminder notification to client
     */
    private function sendClientReminder(Appointment $appointment, int $minutes): void
    {
        $timeText = $this->getTimeText($minutes);
        $serviceName = $appointment->service ? $appointment->service->name : 
                      ($appointment->comboService ? $appointment->comboService->name : 'your appointment');
        
        $appointmentDate = $appointment->date->format('M d, Y');
        $appointmentTime = Carbon::parse($appointment->start_time)->format('g:i A');
        
        $title = "Appointment Reminder";
        $body = "Your appointment for {$serviceName} with {$appointment->provider->name} is starting {$timeText} ({$appointmentDate} at {$appointmentTime})";
        
        $data = [
            'type' => 'appointment_reminder',
            'appointment_id' => $appointment->id,
            'reminder_interval' => $minutes,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'service_name' => $serviceName,
            'provider_name' => $appointment->provider->name,
        ];

        try {
            // Send push notification using your notification service
            if (class_exists('App\Services\NotificationService')) {
                $notificationService = app(NotificationService::class);
                $notificationService->sendPushNotification(
                    $appointment->client->id,
                    $title,
                    $body,
                    $data
                );
            }

            // Mark reminder as sent successfully
            AppointmentReminder::markReminderSent(
                $appointment->id,
                $minutes,
                AppointmentReminder::RECIPIENT_CLIENT,
                $appointment->client->id,
                $data,
                true
            );

        } catch (\Exception $e) {
            // Mark reminder as failed
            AppointmentReminder::markReminderSent(
                $appointment->id,
                $minutes,
                AppointmentReminder::RECIPIENT_CLIENT,
                $appointment->client->id,
                $data,
                false,
                $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * Send reminder notification to provider
     */
    private function sendProviderReminder(Appointment $appointment, int $minutes): void
    {
        $timeText = $this->getTimeText($minutes);
        $serviceName = $appointment->service ? $appointment->service->name : 
                      ($appointment->comboService ? $appointment->comboService->name : 'appointment');
        
        $appointmentDate = $appointment->date->format('M d, Y');
        $appointmentTime = Carbon::parse($appointment->start_time)->format('g:i A');
        
        $title = "Appointment Reminder";
        $body = "You have an appointment for {$serviceName} with {$appointment->client->name} starting {$timeText} ({$appointmentDate} at {$appointmentTime})";
        
        $data = [
            'type' => 'appointment_reminder_provider',
            'appointment_id' => $appointment->id,
            'reminder_interval' => $minutes,
            'appointment_date' => $appointmentDate,
            'appointment_time' => $appointmentTime,
            'service_name' => $serviceName,
            'client_name' => $appointment->client->name,
        ];

        try {
            // Send push notification using your notification service
            if (class_exists('App\Services\NotificationService')) {
                $notificationService = app(NotificationService::class);
                $notificationService->sendPushNotification(
                    $appointment->provider->id,
                    $title,
                    $body,
                    $data
                );
            }

            // Mark reminder as sent successfully
            AppointmentReminder::markReminderSent(
                $appointment->id,
                $minutes,
                AppointmentReminder::RECIPIENT_PROVIDER,
                $appointment->provider->id,
                $data,
                true
            );

        } catch (\Exception $e) {
            // Mark reminder as failed
            AppointmentReminder::markReminderSent(
                $appointment->id,
                $minutes,
                AppointmentReminder::RECIPIENT_PROVIDER,
                $appointment->provider->id,
                $data,
                false,
                $e->getMessage()
            );
            throw $e;
        }
    }



    /**
     * Get human-readable time text
     */
    private function getTimeText(int $minutes): string
    {
        if ($minutes >= 1440) {
            $hours = $minutes / 60;
            return "in {$hours} hours";
        } elseif ($minutes >= 60) {
            $hours = $minutes / 60;
            return "in {$hours} hour" . ($hours > 1 ? 's' : '');
        } else {
            return "in {$minutes} minute" . ($minutes > 1 ? 's' : '');
        }
    }
}