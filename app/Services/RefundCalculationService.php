<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Refund;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RefundCalculationService
{
    /**
     * Calculate refund for an appointment based on cancellation timing.
     */
    public function calculateRefund(Appointment $appointment, ?Carbon $cancellationTime = null): RefundCalculation
    {
        $cancellationTime = $cancellationTime ?? now();

        // Validate appointment has payment
        if (! $appointment->payment) {
            return RefundCalculation::ineligible('No payment found for this appointment');
        }

        // Validate payment can be refunded
        if (! $appointment->payment->canBeRefunded()) {
            return RefundCalculation::ineligible('Payment is not eligible for refund');
        }

        // Calculate time difference
        $bookingTime = $this->getBookingDateTime($appointment);
        $hoursBeforeBooking = $this->calculateHoursBeforeBooking($cancellationTime, $bookingTime);

        // If booking time has passed, no refund
        if ($hoursBeforeBooking < 0) {
            return RefundCalculation::ineligible('Booking time has already passed');
        }

        // Get payment breakdown
        $paymentBreakdown = $this->extractPaymentBreakdown($appointment->payment);

        // Determine policy tier and calculate refund
        return $this->calculateRefundByPolicy($hoursBeforeBooking, $paymentBreakdown);
    }

    /**
     * Get the booking date and time as a Carbon instance.
     */
    protected function getBookingDateTime(Appointment $appointment): Carbon
    {
        return $appointment->date->setTimeFromTimeString($appointment->start_time->format('H:i:s'));
    }

    /**
     * Calculate hours between cancellation and booking time.
     */
    protected function calculateHoursBeforeBooking(Carbon $cancellationTime, Carbon $bookingTime): float
    {
        return $cancellationTime->diffInHours($bookingTime, false);
    }

    /**
     * Extract payment breakdown from payment model.
     */
    protected function extractPaymentBreakdown(Payment $payment): array
    {
        return [
            'original_amount' => (float) $payment->amount,
            'service_charges' => (float) ($payment->booking_price ?? 0),
            'platform_fee' => (float) ($payment->platform_fee ?? 0),
            'other_charges' => (float) ($payment->other_charges ?? 0),
            'gst_amount' => (float) ($payment->gst_amount ?? 0),
        ];
    }

    /**
     * Calculate refund based on policy tier determined by hours before booking.
     */
    protected function calculateRefundByPolicy(float $hoursBeforeBooking, array $paymentBreakdown): RefundCalculation
    {
        if ($hoursBeforeBooking > 24) {
            // Full refund (>24 hours)
            return RefundCalculation::fullRefund(
                $paymentBreakdown['original_amount'],
                $paymentBreakdown['service_charges'],
                $paymentBreakdown['platform_fee'],
                $paymentBreakdown['other_charges'],
                $paymentBreakdown['gst_amount'],
                $hoursBeforeBooking
            );
        } elseif ($hoursBeforeBooking >= 2) {
            // Partial refund (24h-2h)
            return RefundCalculation::partialRefund(
                $paymentBreakdown['original_amount'],
                $paymentBreakdown['service_charges'],
                $paymentBreakdown['platform_fee'],
                $paymentBreakdown['other_charges'],
                $paymentBreakdown['gst_amount'],
                $hoursBeforeBooking
            );
        } else {
            // No refund (<2 hours)
            return RefundCalculation::noRefund(
                $paymentBreakdown['original_amount'],
                $paymentBreakdown['service_charges'],
                $paymentBreakdown['platform_fee'],
                $paymentBreakdown['other_charges'],
                $paymentBreakdown['gst_amount'],
                $hoursBeforeBooking
            );
        }
    }

    /**
     * Get refund policy information for a given time difference.
     */
    public function getRefundPolicy(Carbon $bookingTime, Carbon $cancellationTime): array
    {
        $hoursBeforeBooking = $this->calculateHoursBeforeBooking($cancellationTime, $bookingTime);

        if ($hoursBeforeBooking > 24) {
            return [
                'tier' => Refund::TIER_FULL_REFUND,
                'customer_percentage' => 100,
                'vendor_percentage' => 0,
                'admin_percentage' => 0,
                'description' => 'Full refund - Cancellation more than 24 hours before booking',
                'hours_before_booking' => $hoursBeforeBooking,
            ];
        } elseif ($hoursBeforeBooking >= 2) {
            return [
                'tier' => Refund::TIER_PARTIAL_REFUND,
                'customer_percentage' => 75,
                'vendor_percentage' => 15,
                'admin_percentage' => 10,
                'description' => 'Partial refund - Cancellation between 24 hours and 2 hours before booking',
                'hours_before_booking' => $hoursBeforeBooking,
            ];
        } else {
            return [
                'tier' => Refund::TIER_NO_REFUND,
                'customer_percentage' => 0,
                'vendor_percentage' => 50,
                'admin_percentage' => 50,
                'description' => 'No refund - Cancellation within 2 hours of booking time',
                'hours_before_booking' => $hoursBeforeBooking,
            ];
        }
    }

    /**
     * Validate if a payment is eligible for refund.
     */
    public function validateRefundEligibility(Payment $payment): bool
    {
        try {
            // Check payment status
            if ($payment->status !== Payment::STATUS_PAID) {
                Log::info("Payment {$payment->id} not eligible: status is {$payment->status}");

                return false;
            }

            // Check for existing active refunds
            if ($payment->hasActiveRefund()) {
                Log::info("Payment {$payment->id} not eligible: has active refund");

                return false;
            }

            // Check if appointment exists
            if (! $payment->appointment) {
                Log::info("Payment {$payment->id} not eligible: no associated appointment");

                return false;
            }

            // Check if appointment can be cancelled
            if (! $payment->appointment->canBeCancelled()) {
                Log::info("Payment {$payment->id} not eligible: appointment cannot be cancelled");

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Error validating refund eligibility for payment {$payment->id}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Calculate refund deadlines for an appointment.
     */
    public function calculateRefundDeadlines(Appointment $appointment): array
    {
        $bookingTime = $this->getBookingDateTime($appointment);
        $now = now();

        return [
            'booking_time' => $bookingTime,
            'full_refund_deadline' => $bookingTime->copy()->subHours(24),
            'partial_refund_deadline' => $bookingTime->copy()->subHours(2),
            'no_refund_after' => $bookingTime->copy()->subHours(2),
            'current_time' => $now,
            'hours_until_booking' => $now->diffInHours($bookingTime, false),
            'current_policy' => $this->getRefundPolicy($bookingTime, $now),
            'is_past_booking' => $bookingTime->isPast(),
        ];
    }

    /**
     * Estimate refund amount for a given appointment at current time.
     */
    public function estimateRefund(Appointment $appointment): array
    {
        $calculation = $this->calculateRefund($appointment);
        $deadlines = $this->calculateRefundDeadlines($appointment);

        return [
            'eligible' => $calculation->eligible,
            'reason' => $calculation->reason,
            'customer_refund' => $calculation->customerRefund,
            'formatted_customer_refund' => $calculation->getFormattedCustomerRefund(),
            'vendor_compensation' => $calculation->vendorCompensation,
            'formatted_vendor_compensation' => $calculation->getFormattedVendorCompensation(),
            'admin_retention' => $calculation->adminRetention,
            'formatted_admin_retention' => $calculation->getFormattedAdminRetention(),
            'policy_tier' => $calculation->policyTier,
            'human_policy_tier' => $calculation->getHumanPolicyTier(),
            'hours_before_booking' => $calculation->hoursBeforeBooking,
            'breakdown' => $calculation->breakdown,
            'deadlines' => $deadlines,
        ];
    }

    /**
     * Validate refund calculation integrity.
     */
    public function validateCalculation(RefundCalculation $calculation): array
    {
        $errors = [];

        // Check if calculation is valid
        if (! $calculation->isValid()) {
            $errors[] = 'Refund calculation totals do not match expected amounts';
        }

        // Check negative amounts
        if ($calculation->customerRefund < 0) {
            $errors[] = 'Customer refund amount cannot be negative';
        }

        if ($calculation->vendorCompensation < 0) {
            $errors[] = 'Vendor compensation amount cannot be negative';
        }

        if ($calculation->adminRetention < 0) {
            $errors[] = 'Admin retention amount cannot be negative';
        }

        // Check policy tier consistency
        if ($calculation->eligible) {
            switch ($calculation->policyTier) {
                case Refund::TIER_FULL_REFUND:
                    if ($calculation->hoursBeforeBooking <= 24) {
                        $errors[] = 'Full refund policy requires more than 24 hours before booking';
                    }
                    break;
                case Refund::TIER_PARTIAL_REFUND:
                    if ($calculation->hoursBeforeBooking < 2 || $calculation->hoursBeforeBooking > 24) {
                        $errors[] = 'Partial refund policy requires between 2 and 24 hours before booking';
                    }
                    break;
                case Refund::TIER_NO_REFUND:
                    if ($calculation->hoursBeforeBooking >= 2) {
                        $errors[] = 'No refund policy should only apply within 2 hours of booking';
                    }
                    break;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
