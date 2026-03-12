<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id', 'user_id', 'provider_id', 'transaction_id', 'payment_method',
        'payment_mode', 'amount', 'currency', 'status', 'payment_details',
        'original_price', 'booking_price', 'platform_fee', 'other_charges', 'gst_amount',
        'discount_amount', 'discount_percentage', 'home_visit_fee',
        'additional_services_fee', 'net_amount', 'coupon_code',
        'offer_title', 'vendor_offer_id', 'admin_offer_id', 'offer_type',
        'additional_notes', 'vendor_earnings', 'admin_earnings',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'original_price' => 'decimal:2',
        'booking_price' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'home_visit_fee' => 'decimal:2',
        'additional_services_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'vendor_earnings' => 'decimal:2',
        'admin_earnings' => 'decimal:2',
        'payment_details' => 'json',
    ];

    /**
     * Payment status constants.
     */
    const STATUS_PENDING = 'pending';

    const STATUS_PAID = 'paid';

    const STATUS_FAILED = 'failed';

    const STATUS_REFUNDED = 'refunded';

    /**
     * Get the appointment associated with this payment.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the user who made the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the service provider for this payment.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Check if the payment is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if the payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the payment has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the payment has been refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    /**
     * Get formatted amount with currency symbol.
     */
    public function getFormattedAmountAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->amount, 2);
    }

    /**
     * Get formatted original price with currency symbol.
     */
    public function getFormattedOriginalPriceAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->original_price, 2);
    }

    /**
     * Get formatted booking price with currency symbol.
     */
    public function getFormattedBookingPriceAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->booking_price, 2);
    }

    /**
     * Get formatted platform fee with currency symbol.
     */
    public function getFormattedPlatformFeeAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->platform_fee, 2);
    }

    /**
     * Get formatted other charges with currency symbol.
     */
    public function getFormattedOtherChargesAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->other_charges, 2);
    }

    /**
     * Get formatted GST amount with currency symbol.
     */
    public function getFormattedGstAmountAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->gst_amount, 2);
    }

    /**
     * Get formatted discount amount with currency symbol.
     */
    public function getFormattedDiscountAmountAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->discount_amount, 2);
    }

    /**
     * Get formatted home visit fee with currency symbol.
     */
    public function getFormattedHomeVisitFeeAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->home_visit_fee, 2);
    }

    /**
     * Get formatted additional services fee with currency symbol.
     */
    public function getFormattedAdditionalServicesFeeAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->additional_services_fee, 2);
    }

    /**
     * Get formatted net amount with currency symbol.
     */
    public function getFormattedNetAmountAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->net_amount, 2);
    }

    /**
     * Get formatted vendor earnings with currency symbol.
     */
    public function getFormattedVendorEarningsAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';

        return $symbol.number_format($this->vendor_earnings ?? $this->booking_price, 2);
    }

    /**
     * Get formatted admin earnings with currency symbol.
     */
    public function getFormattedAdminEarningsAttribute(): string
    {
        $symbol = $this->currency === 'INR' ? '₹' : '$';
        $adminEarnings = $this->admin_earnings ??
            ($this->platform_fee + $this->other_charges + $this->gst_amount);

        return $symbol.number_format($adminEarnings, 2);
    }

    /**
     * Get payment method display name.
     */
    public function getPaymentMethodDisplayAttribute(): string
    {
        $methods = [
            'phonepe' => 'PhonePe',
            'card' => 'Credit/Debit Card',
            'upi' => 'UPI',
            'wallet' => 'Wallet',
            'netbanking' => 'Net Banking',
            'cash' => 'Cash',
        ];

        $method = strtolower($this->payment_method);

        return $methods[$method] ?? $this->payment_method;
    }

    /**
     * Get status badge class for UI display.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        switch ($this->status) {
            case self::STATUS_PAID:
                return 'bg-green-100 text-green-800';
            case self::STATUS_PENDING:
                return 'bg-yellow-100 text-yellow-800';
            case self::STATUS_FAILED:
                return 'bg-red-100 text-red-800';
            case self::STATUS_REFUNDED:
                return 'bg-blue-100 text-blue-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Get a human-readable status.
     */
    public function getHumanStatusAttribute(): string
    {
        switch ($this->status) {
            case self::STATUS_PAID:
                return 'Paid';
            case self::STATUS_PENDING:
                return 'Pending';
            case self::STATUS_FAILED:
                return 'Failed';
            case self::STATUS_REFUNDED:
                return 'Refunded';
            default:
                return ucfirst($this->status);
        }
    }

    /**
     * Calculate and get the breakdown of payment.
     */
    public function getPaymentBreakdownAttribute(): array
    {
        // Calculate vendor earnings and admin earnings if not already set
        $vendorEarnings = $this->vendor_earnings ?? $this->booking_price;
        $adminEarnings = $this->admin_earnings ??
            ($this->platform_fee + $this->other_charges + $this->gst_amount);

        return [
            'original_price' => $this->original_price ?? 0,
            'base_amount' => $this->booking_price ?? 0,
            'gst_amount' => $this->gst_amount ?? 0,
            'platform_fee' => $this->platform_fee ?? 0,
            'other_charges' => $this->other_charges ?? 0,
            'discount_amount' => $this->discount_amount ?? 0,
            'discount_percentage' => $this->discount_percentage ?? 0,
            'home_visit_fee' => $this->home_visit_fee ?? 0,
            'additional_services_fee' => $this->additional_services_fee ?? 0,
            'total_amount' => $this->amount,
            'net_amount' => $this->net_amount ?? $this->amount,
            'vendor_earnings' => $vendorEarnings,
            'admin_earnings' => $adminEarnings,
            'coupon_code' => $this->coupon_code,
            'offer_title' => $this->offer_title,
            'offer_type' => $this->offer_type,
        ];
    }
}
