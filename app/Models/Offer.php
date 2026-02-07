<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    use HasFactory;

    // Define offer types as constants
    const TYPE_ADMIN = 'admin';

    const TYPE_PROVIDER = 'vendor';

    protected $fillable = [
        'user_id',          // NULL for admin offers, provider ID for provider offers
        'service_id',       // Optional: specific service the offer applies to
        'offer_type',       // 'admin' or 'vendor'
        'title',
        'description',
        'discount_percentage',
        'coupon_code',      // Required for admin offers, optional for provider offers
        'start_date',
        'end_date',
        'is_active',
        'usage_limit',      // NULL for unlimited (default for provider offers)
        'used_count',       // Tracking usage (always start at 0)
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
    ];

    /**
     * Get the user that owns the offer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service that the offer applies to.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Scope to get admin offers
     */
    public function scopeAdmin($query)
    {
        return $query->where('offer_type', self::TYPE_ADMIN);
    }

    /**
     * Scope to get provider offers
     */
    public function scopeProvider($query)
    {
        return $query->where('offer_type', self::TYPE_PROVIDER);
    }

    /**
     * Scope for a specific provider's offers
     */
    public function scopeForProvider($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->where('offer_type', self::TYPE_PROVIDER);
    }

    /**
     * Scope a query to only include active offers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to include only current offers (within date range).
     */
    public function scopeCurrent($query)
    {
        $today = now()->format('Y-m-d');

        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    /**
     * Scope a query to include only offers that haven't reached their usage limit.
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('usage_limit')
                ->orWhereRaw('used_count < usage_limit');
        });
    }

    /**
     * Check if the offer is currently valid.
     */
    public function isValid()
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->format('Y-m-d');
        if ($today < $this->start_date->format('Y-m-d') || $today > $this->end_date->format('Y-m-d')) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Increment the used count of the offer.
     */
    public function incrementUsedCount()
    {
        $this->increment('used_count');
    }

    /**
     * Determine if this is an admin offer
     */
    public function isAdminOffer()
    {
        return $this->offer_type === self::TYPE_ADMIN;
    }

    /**
     * Determine if this is a provider offer
     */
    public function isProviderOffer()
    {
        return $this->offer_type === self::TYPE_PROVIDER;
    }
}
