<?php

namespace App\Notifications;

use App\Traits\SendSmsTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionReminderNotification extends Notification
{
    use Queueable;
    use SendSmsTrait;

    public function __construct(public string $targetDate) {}

    public function via($notifiable)
    {
        //$channels = ['mail', 'database'];
        //$channels = ['mail'];

        // If user has a phone number, also send SMS manually
        if (!empty($notifiable->mobile)) {
            $this->sendViaSms($notifiable->mobile, []);
        }
        Log::info('via');
        //return $channels;
    }

    public function toMail($notifiable)
    {
        // Log::info('toMail');
        // return (new MailMessage)
        //     ->subject('Subscription Reminder')
        //     ->greeting('Hello ' . $notifiable->name . ',')
        //     ->line($this->message)
        //     ->action('Renew Subscription', url('/subscriptions'))
        //     ->line('Thank you for using our service!');
    }


    // Custom SMS sender (replace with your API)
    protected function sendViaSms($phone, $message)
    {
        Log::info('sendSms');
        // Example: MSG91 / Fast2SMS / any other HTTP SMS API
        $type = "subscription_expired";
        $this->sendSms($phone, $type, ['date' => $this->targetDate]);
        //Your INSTA APPOINT subscription is about to expire on [24 nov 2025] Please renew it to continue enjoying our services. https://www.instaappoint.in/
    }

    public function toArray($notifiable)
    {
        Log::info('toArray');
        return ['message' => $this->targetDate];
    }

    // public function databaseType($notifiable)
    // {
    //     Log::info('databaseType');
    //     return \App\Models\SubscriptionNotificationRecord::class;
    // }
}
