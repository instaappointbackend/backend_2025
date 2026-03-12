<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SubscriptionReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendSubscriptionReminder extends Command
{
    protected $signature = 'notify:subscriptions {days_before}';

    protected $description = 'Send subscription reminders X days before expiry';

    public function handle()
    {
        $daysBefore = (int) $this->argument('days_before');
        $today = Carbon::now();
        $targetDate = $today->copy()->addDays($daysBefore)->toDateString();
        $formated_date = $today->copy()->addDays($daysBefore)->format('d M Y');

        // dd($targetDate);
        if ($daysBefore === 999) {
            // Monthly reminder on 1st of month
            $subscriptions = Subscription::whereNotNull('expires_at')->where('status', 'active')->get();
            foreach ($subscriptions as $subscription) {
                $subscription->user->notify(new SubscriptionReminderNotification('Your subscription is active this month.'));
            }
            $this->info('Monthly active reminders sent.');

            return;
        }

        // Get users whose subscription ends in X days
        $subscriptions = Subscription::with('user')->whereDate('expires_at', $targetDate)->get();

        if ($subscriptions->isEmpty()) {
            $this->info("No users found for {$daysBefore}-day reminder.");

            return;
        }

        foreach ($subscriptions as $subscription) {
            $message = match ($daysBefore) {
                30 => 'Your subscription will expire in 1 month.',
                15 => 'Your subscription will expire in 15 days.',
                7 => 'Your subscription will expire in 1 week.',
                1 => 'Your subscription will expire tomorrow.',
                0 => 'Your subscription expires today!',
                default => "Your subscription ends in {$daysBefore} days."
            };

            $subscription->user->notify(new SubscriptionReminderNotification($formated_date));
            // Log info
            // $this->info('Subscription reminder sent', [
            //     'user_id' => $subscription->user->id,
            //     'user_email' => $subscription->user->email,
            //     'days_before' => $daysBefore,
            //     'message' => $message,
            // ]);
        }

        $this->info("Reminders sent for subscriptions expiring in {$daysBefore} days.");
    }
}
