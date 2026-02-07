<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'payment_id',
        'user_id',
        'provider_id',
        'refund_amount',
        'vendor_amount',
        'admin_amount',
        'refund_type',
        'refund_reason',
        'refund_status',
        'refund_reference',
        'processed_at',
        'refund_details',
        'cancellation_time_hours',
        'original_amount',
        'service_charges',
        'platform_fee',
        'other_charges',
        'gst_amount',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'vendor_amount' => 'decimal:2',
        'admin_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'service_charges' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'refund_details' => 'json',
        'cancellation_time_hours' => 'decimal:2',
    ];

    // Refund types based on cancellation timing
    const TYPE_FULL_REFUND = 'full_refund'; // Before 24 hours

    const TYPE_PARTIAL_REFUND = 'partial_refund'; // Between 24-2 hours

    const TYPE_NO_REFUND = 'no_refund'; // Within 2 hours or after booking time

    // Refund status
    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSED = 'processed';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the appointment associated with this refund.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the payment associated with this refund.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the user who requested the refund.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service provider for this refund.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Check if the refund is pending.
     */
    public function isPending(): bool
    {
        return $this->refund_status === self::STATUS_PENDING;
    }

    /**
     * Check if the refund is processed.
     */
    public function isProcessed(): bool
    {
        return $this->refund_status === self::STATUS_PROCESSED;
    }

    /**
     * Check if the refund has failed.
     */
    public function hasFailed(): bool
    {
        return $this->refund_status === self::STATUS_FAILED;
    }

    /**
     * Get formatted refund amount with currency symbol.
     */
    public function getFormattedRefundAmountAttribute(): string
    {
        return '₹'.number_format($this->refund_amount, 2);
    }

    /**
     * Get formatted vendor amount with currency symbol.
     */
    public function getFormattedVendorAmountAttribute(): string
    {
        return '₹'.number_format($this->vendor_amount, 2);
    }

    /**
     * Get formatted admin amount with currency symbol.
     */
    public function getFormattedAdminAmountAttribute(): string
    {
        return '₹'.number_format($this->admin_amount, 2);
    }

    /**
     * Get status badge class for UI display.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        switch ($this->refund_status) {
            case self::STATUS_PROCESSED:
                return 'bg-green-100 text-green-800';
            case self::STATUS_PENDING:
                return 'bg-yellow-100 text-yellow-800';
            case self::STATUS_FAILED:
                return 'bg-red-100 text-red-800';
            case self::STATUS_CANCELLED:
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Get a human-readable status.
     */
    public function getHumanStatusAttribute(): string
    {
        switch ($this->refund_status) {
            case self::STATUS_PROCESSED:
                return 'Processed';
            case self::STATUS_PENDING:
                return 'Pending';
            case self::STATUS_FAILED:
                return 'Failed';
            case self::STATUS_CANCELLED:
                return 'Cancelled';
            default:
                return ucfirst($this->refund_status);
        }
    }

    /**
     * Get refund type display name.
     */
    public function getRefundTypeDisplayAttribute(): string
    {
        switch ($this->refund_type) {
            case self::TYPE_FULL_REFUND:
                return 'Full Refund (100%)';
            case self::TYPE_PARTIAL_REFUND:
                return 'Partial Refund (75%)';
            case self::TYPE_NO_REFUND:
                return 'No Refund';
            default:
                return ucfirst(str_replace('_', ' ', $this->refund_type));
        }
    }
}
