<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;

class SubscriptionNotificationRecord extends DatabaseNotification
{
    protected $table = 'subscription_notifications';
}
