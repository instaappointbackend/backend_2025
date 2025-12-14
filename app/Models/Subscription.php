<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_name',
        'amount',
        'phonepe_transaction_id',
        //'phonepe_merchant_transaction_id',
        'status',
        'starts_at',
        'expires_at',
        'last_notification_sent_at'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_notification_sent_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Set start date and automatically calculate expires_at
     *
     * @param int $durationValue
     * @param string $durationUnit 'day', 'month', 'year'
     * @param Carbon|null $startDate
     * @return void
     */
    public function setDuration(int $durationValue, string $durationUnit, ?Carbon $startDate = null)
    {
        $this->starts_at = $startDate ?? now();

        $this->expires_at = match ($durationUnit) {
            'day' => $this->starts_at->copy()->addDays($durationValue),
            'month' => $this->starts_at->copy()->addMonths($durationValue),
            'year' => $this->starts_at->copy()->addYears($durationValue),
        };
    }

    /**
     * Check if subscription is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture() && $this->status === 'completed';
    }

    /**
     * Get remaining days until expiry
     *
     * @return int|null
     */
    public function getRemainingDaysAttribute(): ?int
    {
        return $this->expires_at ? now()->diffInDays($this->expires_at, false) : null;
    }

    /**
     * Shortcut to get expiry date
     *
     * @return Carbon|null
     */
    public function getExpiryAttribute(): ?Carbon
    {
        return $this->expires_at;
    }
}
