<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment1 extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'service_id',
        'combo_service_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'notes',
        'payment_status',
        'payment_id',
        'payment_method',
        'payment_mode',
        'payment_amount',

        // Detailed payment breakdown fields
        'booking_price',
        'platform_fees',
        'other_charges',
        'gst',
        'original_price',
        'discount_amount',
        'discount_percentage',
        'home_visit_fee',
        'additional_services_fee',
        'final_price',

        // Discount and offer related fields
        'coupon_code',
        'offer_title',
        'vendor_offer_id',
        'admin_offer_id',
        'offer_type',

        // Visit type
        'visit_type',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'payment_amount' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'final_price' => 'decimal:2',
        'booking_price' => 'decimal:2',
        'platform_fees' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'gst' => 'decimal:2',
        'home_visit_fee' => 'decimal:2',
        'additional_services_fee' => 'decimal:2',
    ];

    /**
     * The possible status values for an appointment.
     */
    const STATUS_PENDING = 'pending';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_COMPLETED = 'completed';

    /**
     * The possible payment status values for an appointment.
     */
    const PAYMENT_STATUS_PENDING = 'pending';

    const PAYMENT_STATUS_PAID = 'paid';

    const PAYMENT_STATUS_FAILED = 'failed';

    const PAYMENT_STATUS_REFUNDED = 'refunded';

    /**
     * Get the user (vendor) that owns the appointment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client for this appointment.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the client for this appointment.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the service for this appointment.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the combo service for this appointment.
     */
    public function comboService(): BelongsTo
    {
        return $this->belongsTo(ComboService::class);
    }

    /**
     * Get the payment for this appointment.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Determine if this appointment is for a combo service.
     */
    public function isComboService(): bool
    {
        return $this->combo_service_id !== null;
    }

    /**
     * Get formatted platform fees with currency symbol.
     */
    public function getFormattedPlatformFeesAttribute(): string
    {
        return '₹'.number_format($this->platform_fees, 2);
    }

    /**
     * Get formatted other charges with currency symbol.
     */
    public function getFormattedOtherChargesAttribute(): string
    {
        return '₹'.number_format($this->other_charges, 2);
    }

    /**
     * Get formatted GST with currency symbol.
     */
    public function getFormattedGstAttribute(): string
    {
        return '₹'.number_format($this->gst, 2);
    }

    /**
     * Get formatted home visit fee with currency symbol.
     */
    public function getFormattedHomeVisitFeeAttribute(): string
    {
        return '₹'.number_format($this->home_visit_fee, 2);
    }

    /**
     * Get formatted additional services fee with currency symbol.
     */
    public function getFormattedAdditionalServicesFeeAttribute(): string
    {
        return '₹'.number_format($this->additional_services_fee, 2);
    }

    /**
     * Get detailed payment breakdown for this appointment.
     */
    public function getPaymentBreakdownAttribute(): array
    {
        return [
            'booking_price' => $this->booking_price ?? 0,
            'platform_fees' => $this->platform_fees ?? 0,
            'other_charges' => $this->other_charges ?? 0,
            'gst' => $this->gst ?? 0,
            'original_price' => $this->original_price ?? 0,
            'discount_amount' => $this->discount_amount ?? 0,
            'discount_percentage' => $this->discount_percentage ?? 0,
            'home_visit_fee' => $this->home_visit_fee ?? 0,
            'additional_services_fee' => $this->additional_services_fee ?? 0,
            'final_price' => $this->final_price ?? $this->payment_amount ?? 0,
            'payment_amount' => $this->payment_amount ?? 0,
            'coupon_code' => $this->coupon_code,
            'offer_title' => $this->offer_title,
            'offer_type' => $this->offer_type,
        ];
    }

    /**
     * Get the services included in this appointment.
     * Returns array of service objects if it's a combo service,
     * or an array with a single service if it's a regular appointment.
     */
    public function getServicesAttribute()
    {
        if ($this->isComboService()) {
            return $this->comboService->services ?? [];
        }

        return [$this->service];
    }

    /**
     * Get the discount percentage for this appointment.
     */
    public function getDiscountPercentageAttribute()
    {
        if ($this->isComboService() && $this->comboService) {
            return $this->comboService->discount_percentage;
        }

        return 0;
    }

    /**
     * Scope a query to only include pending appointments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include confirmed appointments.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope a query to only include cancelled appointments.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope a query to only include completed appointments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include upcoming appointments.
     */
    public function scopeUpcoming($query)
    {
        $today = Carbon::today();

        return $query->where('date', '>=', $today)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    /**
     * Scope a query to filter appointments by date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope a query to only include appointments with specific payment status.
     */
    public function scopePaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Get the formatted date and time of the appointment.
     */
    public function getFormattedTimeAttribute()
    {
        $date = $this->date->format('F j, Y');
        $start = Carbon::parse($this->start_time)->format('g:i A');
        $end = Carbon::parse($this->end_time)->format('g:i A');

        return "$date, $start - $end";
    }

    /**
     * Get the duration of the appointment in minutes.
     */
    public function getDurationInMinutesAttribute()
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $end->diffInMinutes($start);
    }

    /**
     * Check if the appointment can be cancelled.
     */
    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]) &&
            ($this->date->isAfter(Carbon::today()) || $this->date->isToday());
    }

    /**
     * Check if the appointment is paid.
     */
    public function isPaid()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    /**
     * Check if payment is pending for the appointment.
     */
    public function isPaymentPending()
    {
        return $this->payment_status === self::PAYMENT_STATUS_PENDING;
    }

    /**
     * Check if payment has failed for the appointment.
     */
    public function hasPaymentFailed()
    {
        return $this->payment_status === self::PAYMENT_STATUS_FAILED;
    }

    /**
     * Check if payment has been refunded for the appointment.
     */
    public function isPaymentRefunded()
    {
        return $this->payment_status === self::PAYMENT_STATUS_REFUNDED;
    }

    /**
     * Get formatted payment amount with currency symbol.
     */
    public function getFormattedPaymentAmountAttribute()
    {
        if (! $this->payment_amount) {
            return null;
        }

        return '₹'.number_format($this->payment_amount, 2);
    }

    /**
     * Get formatted original price with currency symbol.
     */
    public function getFormattedOriginalPriceAttribute()
    {
        if (! $this->original_price) {
            return null;
        }

        return '₹'.number_format($this->original_price, 2);
    }

    /**
     * Get formatted discount amount with currency symbol.
     */
    public function getFormattedDiscountAmountAttribute()
    {
        if (! $this->discount_amount) {
            return null;
        }

        return '₹'.number_format($this->discount_amount, 2);
    }

    /**
     * Get formatted final price with currency symbol.
     */
    public function getFormattedFinalPriceAttribute()
    {
        if (! $this->final_price) {
            return null;
        }

        return '₹'.number_format($this->final_price, 2);
    }

    /**
     * Mark the appointment as confirmed.
     */
    public function confirm()
    {
        $this->update(['status' => self::STATUS_CONFIRMED]);
    }

    /**
     * Mark the appointment as cancelled.
     */
    public function cancel()
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Mark the appointment as completed.
     */
    public function complete()
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Update the payment status of the appointment.
     */
    public function updatePaymentStatus($status)
    {
        $this->update(['payment_status' => $status]);
    }

    /**
     * Get status badge class for UI display.
     */
    public function getStatusBadgeClassAttribute()
    {
        switch ($this->status) {
            case self::STATUS_CONFIRMED:
                return 'bg-green-100 text-green-800';
            case self::STATUS_PENDING:
                return 'bg-yellow-100 text-yellow-800';
            case self::STATUS_CANCELLED:
                return 'bg-red-100 text-red-800';
            case self::STATUS_COMPLETED:
                return 'bg-blue-100 text-blue-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Get payment status badge class for UI display.
     */
    public function getPaymentStatusBadgeClassAttribute()
    {
        switch ($this->payment_status) {
            case self::PAYMENT_STATUS_PAID:
                return 'bg-green-100 text-green-800';
            case self::PAYMENT_STATUS_PENDING:
                return 'bg-yellow-100 text-yellow-800';
            case self::PAYMENT_STATUS_FAILED:
                return 'bg-red-100 text-red-800';
            case self::PAYMENT_STATUS_REFUNDED:
                return 'bg-blue-100 text-blue-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Get a human-readable status.
     */
    public function getHumanStatusAttribute()
    {
        switch ($this->status) {
            case self::STATUS_CONFIRMED:
                return 'Confirmed';
            case self::STATUS_PENDING:
                return 'Pending';
            case self::STATUS_CANCELLED:
                return 'Cancelled';
            case self::STATUS_COMPLETED:
                return 'Completed';
            default:
                return ucfirst($this->status);
        }
    }

    /**
     * Get a human-readable payment status.
     */
    public function getHumanPaymentStatusAttribute()
    {
        switch ($this->payment_status) {
            case self::PAYMENT_STATUS_PAID:
                return 'Paid';
            case self::PAYMENT_STATUS_PENDING:
                return 'Pending';
            case self::PAYMENT_STATUS_FAILED:
                return 'Failed';
            case self::PAYMENT_STATUS_REFUNDED:
                return 'Refunded';
            default:
                return ucfirst($this->payment_status);
        }
    }

    /**
     * Check if the appointment can be rescheduled.
     */
    public function canBeRescheduled()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]) &&
            ($this->date->isAfter(Carbon::today()) || $this->date->isToday());
    }

    /**
     * Check if the appointment can be completed.
     */
    public function canBeCompleted()
    {
        return $this->status === self::STATUS_CONFIRMED &&
            ($this->date->isToday() || $this->date->isPast());
    }

    /**
     * Check if the user can leave a review for this appointment.
     */
    public function canLeaveReview()
    {
        return $this->status === self::STATUS_COMPLETED &&
            $this->date->isPast() &&
            ! Review::where('appointment_id', $this->id)->exists();
    }

    /**
     * Get the appointment type (service or combo).
     */
    public function getAppointmentTypeAttribute()
    {
        return $this->isComboService() ? 'combo' : 'service';
    }

    /**
     * Get the appointment title based on its type.
     */
    public function getAppointmentTitleAttribute()
    {
        if ($this->isComboService() && $this->comboService) {
            return $this->comboService->name;
        } elseif ($this->service) {
            return $this->service->name;
        }

        return 'Appointment';
    }
}
