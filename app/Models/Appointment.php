<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
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

    // Add new status constants
    const STATUS_DRAFT = 'draft';

    const STATUS_PAYMENT_PENDING = 'payment_pending';

    /**
     * Check if appointment is in a payment pending state
     */
    public function isPhonePePaymentPending()
    {
        return $this->status === self::STATUS_PAYMENT_PENDING;
    }

    /**
     * Check if appointment is a draft
     */
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Mark appointment as confirmed after successful payment
     */
    public function confirmAfterPayment()
    {
        $this->update([
            'status' => self::STATUS_PENDING, // or CONFIRMED based on auto-confirm setting
        ]);
    }

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
     * Get the refund for this appointment.
     */
    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }

    /**
     * Helper method to manage time slots for multi-slot bookings.
     * This method can be used to either block or free time slots.
     *
     * @param  bool  $makeAvailable  True to make slots available, false to block them
     * @return void
     */
    public function manageTimeSlots($makeAvailable = false)
    {
        // Get the service duration
        $serviceDuration = 0;

        if ($this->service_id) {
            $service = Service::find($this->service_id);
            if ($service) {
                $serviceDuration = $service->duration;
            }
        } elseif ($this->combo_service_id) {
            $comboService = ComboService::with('services')->find($this->combo_service_id);
            if ($comboService) {
                $serviceDuration = $comboService->total_duration;
            }
        }

        // If we couldn't determine service duration, default to difference between start and end time
        if (! $serviceDuration && $this->start_time && $this->end_time) {
            $startTime = Carbon::parse($this->start_time);
            $endTime = Carbon::parse($this->end_time);
            $serviceDuration = $endTime->diffInMinutes($startTime);
        }

        // Get provider's appointment settings
        $settings = AppointmentSettings::where('user_id', $this->user_id)->first();
        $slotDuration = $settings ? $settings->appointment_duration : 30; // Default to 30 min
        $bufferTime = $settings ? $settings->buffer_time : 0; // Get buffer time if available

        // Calculate how many slots the appointment needs
        $slotsNeeded = ceil($serviceDuration / $slotDuration);

        // Get the starting time slot
        $startingTimeSlot = TimeSlot::where('user_id', $this->user_id)
            ->where('date', $this->date)
            ->where('start_time', $this->start_time)
            ->first();

        if ($startingTimeSlot) {
            // Update the starting time slot
            if ($makeAvailable) {
                $startingTimeSlot->update([
                    'is_available' => true,
                    'status' => TimeSlot::STATUS_AVAILABLE,
                    'blocked_until' => null,
                    'blocked_for_appointment_id' => null,
                    'blocked_reason' => null,
                ]);
            } else {
                $startingTimeSlot->update([
                    'is_available' => false,
                    'status' => TimeSlot::STATUS_BOOKED,
                ]);
            }

            // Update consecutive time slots
            $startTime = Carbon::parse($startingTimeSlot->start_time);

            for ($i = 1; $i < $slotsNeeded; $i++) {
                $nextSlotStartTime = (clone $startTime)->addMinutes($slotDuration + $bufferTime);

                $nextSlot = TimeSlot::where('user_id', $this->user_id)
                    ->where('date', $this->date)
                    ->where('start_time', $nextSlotStartTime->format('H:i'))
                    ->first();

                if ($nextSlot) {
                    if ($makeAvailable) {
                        $nextSlot->update([
                            'is_available' => true,
                            'status' => TimeSlot::STATUS_AVAILABLE,
                            'blocked_until' => null,
                            'blocked_for_appointment_id' => null,
                            'blocked_reason' => null,
                        ]);
                    } else {
                        $nextSlot->update([
                            'is_available' => false,
                            'status' => TimeSlot::STATUS_BOOKED,
                        ]);
                    }
                }

                $startTime = $nextSlotStartTime;
            }
        } else {
            // If we couldn't find the exact starting slot, try to find any slots that overlap with this appointment's time range
            $appointmentStartTime = Carbon::parse($this->date->format('Y-m-d').' '.$this->start_time);
            $appointmentEndTime = Carbon::parse($this->date->format('Y-m-d').' '.$this->end_time);

            $timeSlots = TimeSlot::where('user_id', $this->user_id)
                ->where('date', $this->date)
                ->get();

            foreach ($timeSlots as $timeSlot) {
                $slotStartTime = Carbon::parse($timeSlot->date->format('Y-m-d').' '.$timeSlot->start_time);
                $slotEndTime = Carbon::parse($timeSlot->date->format('Y-m-d').' '.$timeSlot->end_time);

                // If this slot overlaps with the appointment time range, update it
                if (($slotStartTime >= $appointmentStartTime && $slotStartTime < $appointmentEndTime) ||
                    ($slotEndTime > $appointmentStartTime && $slotEndTime <= $appointmentEndTime) ||
                    ($slotStartTime <= $appointmentStartTime && $slotEndTime >= $appointmentEndTime)) {

                    if ($makeAvailable) {
                        $timeSlot->update([
                            'is_available' => true,
                            'status' => TimeSlot::STATUS_AVAILABLE,
                            'blocked_until' => null,
                            'blocked_for_appointment_id' => null,
                            'blocked_reason' => null,
                        ]);
                    } else {
                        $timeSlot->update([
                            'is_available' => false,
                            'status' => TimeSlot::STATUS_BOOKED,
                        ]);
                    }
                }
            }
        }
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
    public function canBeCancelled($isAdmin = false)
    {
        // Admin can cancel any appointment regardless of status or date
        if ($isAdmin) {
            return true;
        }

        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_PAYMENT_PENDING]) &&
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
     * Mark the appointment as cancelled and free up time slots.
     */
    public function cancel()
    {
        $this->update(['status' => self::STATUS_CANCELLED]);

        // Free up all time slots associated with this appointment
        $this->manageTimeSlots(true);
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
            case self::STATUS_PAYMENT_PENDING:
                return 'bg-orange-100 text-orange-800';
            case self::STATUS_CANCELLED:
                return 'bg-red-100 text-red-800';
            case self::STATUS_COMPLETED:
                return 'bg-blue-100 text-blue-800';
            case self::STATUS_DRAFT:
                return 'bg-gray-100 text-gray-800';
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
            case self::STATUS_PAYMENT_PENDING:
                return 'Payment Pending';
            case self::STATUS_CANCELLED:
                return 'Cancelled';
            case self::STATUS_COMPLETED:
                return 'Completed';
            case self::STATUS_DRAFT:
                return 'Draft';
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
                return 'Payment Pending';
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
    public function canBeRescheduled($isAdmin = false)
    {
        // Admin can reschedule any appointment regardless of status or date
        if ($isAdmin) {
            return true;
        }

        // Check if appointment status allows rescheduling
        if (! in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_PAYMENT_PENDING])) {
            return false;
        }

        try {
            // Get the start time string
            $rawStartTime = $this->getRawOriginal('start_time');

            if ($rawStartTime) {
                // If raw time exists, use it directly
                $startTimeString = $rawStartTime;
            } else {
                // Fallback to cast value
                $startTimeString = $this->start_time instanceof Carbon ?
                    $this->start_time->format('H:i:s') :
                    (string) $this->start_time;
            }

            // Ensure we have a proper time format (H:i:s or H:i)
            if (! preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $startTimeString)) {
                // If time format is invalid, assume it can be rescheduled (safer default)
                return true;
            }

            // Add seconds if not present
            if (substr_count($startTimeString, ':') === 1) {
                $startTimeString .= ':00';
            }

            // Create full datetime by combining appointment date with start time
            $appointmentStartDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $this->date->format('Y-m-d').' '.$startTimeString
            );

            // Can be rescheduled only if the appointment start datetime is in the future
            return $appointmentStartDateTime->isFuture();

        } catch (\Exception $e) {
            // If there's any error parsing the time, assume it can be rescheduled (safer default)
            return true;
        }
    }

    /**
     * Check if the appointment can be completed.
     */
    //    public function canBeCompleted()
    //  {
    //     return $this->status === self::STATUS_CONFIRMED && $this->date->isAfter(Carbon::today());
    // }

    public function canBeCompleted()
    {
        // Check if appointment is confirmed
        if ($this->status !== self::STATUS_CONFIRMED) {
            return false;
        }

        try {
            // Get the end time string
            $rawEndTime = $this->getRawOriginal('end_time');

            if ($rawEndTime) {
                // If raw time exists, use it directly
                $endTimeString = $rawEndTime;
            } else {
                // Fallback to cast value
                $endTimeString = $this->end_time instanceof Carbon ?
                    $this->end_time->format('H:i:s') :
                    (string) $this->end_time;
            }

            // Ensure we have a proper time format (H:i:s or H:i)
            if (! preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $endTimeString)) {
                // If time format is invalid, assume it can't be completed yet
                return false;
            }

            // Add seconds if not present
            if (substr_count($endTimeString, ':') === 1) {
                $endTimeString .= ':00';
            }

            // Create full datetime by combining appointment date with end time
            $appointmentEndDateTime = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $this->date->format('Y-m-d').' '.$endTimeString
            );

            // Check if both date and end time are in the past
            return $appointmentEndDateTime->isPast();

        } catch (\Exception $e) {
            // If there's any error parsing the time, assume it can't be completed yet
            return false;
        }
    }

    /**
     * Debug method to check completion logic - REMOVE AFTER DEBUGGING
     */
    public function debugCanBeCompleted()
    {
        $now = Carbon::now();
        $endTimeString = $this->end_time instanceof Carbon ? $this->end_time->format('H:i') : $this->end_time;
        $appointmentEndTime = Carbon::parse($this->date->format('Y-m-d').' '.$endTimeString);

        return [
            'status' => $this->status,
            'is_confirmed' => $this->status === self::STATUS_CONFIRMED,
            'appointment_date' => $this->date->format('Y-m-d'),
            'appointment_end_time' => $this->end_time,
            'end_time_string' => $endTimeString,
            'full_end_datetime' => $appointmentEndTime->format('Y-m-d H:i:s'),
            'current_datetime' => $now->format('Y-m-d H:i:s'),
            'date_is_past' => $this->date->isPast(),
            'date_is_today' => $this->date->isToday(),
            'date_is_future' => $this->date->isFuture(),
            'end_time_is_past' => $appointmentEndTime->isPast(),
            'can_be_completed' => $this->canBeCompleted(),
        ];
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
