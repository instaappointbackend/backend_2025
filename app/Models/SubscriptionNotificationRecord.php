<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class SubscriptionNotificationRecord extends DatabaseNotification
{
    protected $table = 'subscription_notifications';
}
