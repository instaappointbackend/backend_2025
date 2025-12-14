<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Refund;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    /**
     * Calculate refund amount based on cancellation policy
     *
     * @param Appointment $appointment
     * @return array
     */
    public function calculateRefund(Appointment $appointment): array
    {
        $payment = $appointment->payment;
        if (!$payment) {
            // Calculate hours for policy display even without payment
            $appointmentDateTime = Carbon::parse($appointment->date->format('Y-m-d') . ' ' . $appointment->start_time->format('H:i:s'));
            $hoursUntilAppointment = Carbon::now()->diffInHours($appointmentDateTime, false);
            
            return [
                'refund_type' => 'no_payment',
                'refund_amount' => 0,
                'vendor_amount' => 0,
                'admin_amount' => 0,
                'cancellation_hours' => $hoursUntilAppointment,
                'original_amount' => 0,
                'service_charges' => 0,
                'platform_fee' => 0,
                'other_charges' => 0,
                'gst_amount' => 0,
                'policy_applied' => 'No payment found - appointment can be cancelled without refund processing'
            ];
        }

        // Calculate hours between now and appointment start time
        $appointmentDateTime = Carbon::parse($appointment->date->format('Y-m-d') . ' ' . $appointment->start_time->format('H:i:s'));
        $hoursUntilAppointment = Carbon::now()->diffInHours($appointmentDateTime, false);

        // Get payment breakdown
        $serviceCharges = $payment->booking_price ?? 0;
        $platformFee = 8; // Flat ₹8 platform fee
        $otherCharges = $serviceCharges * 0.02; // 2% of service charges
        $gstAmount = ($platformFee + $otherCharges) * 0.18; // 18% GST on platform fee + other charges
        $totalPaid = $payment->amount ?? 0;

        $refundData = [
            'cancellation_hours' => $hoursUntilAppointment,
            'original_amount' => $totalPaid,
            'service_charges' => $serviceCharges,
            'platform_fee' => $platformFee,
            'other_charges' => $otherCharges,
            'gst_amount' => $gstAmount,
        ];

        if ($hoursUntilAppointment >= 24) {
            // Policy 1: Cancellation before 24 hours - 100% refund
            $refundData = array_merge($refundData, [
                'refund_type' => Refund::TYPE_FULL_REFUND,
                'refund_amount' => $totalPaid,
                'vendor_amount' => 0,
                'admin_amount' => 0,
                'policy_applied' => 'Full refund - cancelled before 24 hours'
            ]);
        } elseif ($hoursUntilAppointment >= 2) {
            // Policy 2: Cancellation between 24-2 hours - 75% of service charges
            $refundToCustomer = $serviceCharges * 0.75;
            $vendorReceives = $serviceCharges * 0.15; // 15% of service charges
            $adminReceives = $serviceCharges * 0.10; // 10% of service charges

            $refundData = array_merge($refundData, [
                'refund_type' => Refund::TYPE_PARTIAL_REFUND,
                'refund_amount' => $refundToCustomer,
                'vendor_amount' => $vendorReceives,
                'admin_amount' => $adminReceives,
                'policy_applied' => 'Partial refund - cancelled between 24-2 hours'
            ]);
        } else {
            // Policy 3: Cancellation within 2 hours or after booking time - No refund
            $vendorReceives = $serviceCharges * 0.50; // 50% of service charges
            $adminReceives = $serviceCharges * 0.50; // 50% of service charges

            $refundData = array_merge($refundData, [
                'refund_type' => Refund::TYPE_NO_REFUND,
                'refund_amount' => 0,
                'vendor_amount' => $vendorReceives,
                'admin_amount' => $adminReceives,
                'policy_applied' => 'No refund - cancelled within 2 hours or after booking time'
            ]);
        }

        return $refundData;
    }

    /**
     * Process refund for cancelled appointment
     *
     * @param Appointment $appointment
     * @param string $reason
     * @param int|null $userId
     * @return Refund|null
     * @throws \Exception
     */
    public function processRefund(Appointment $appointment, string $reason = 'Appointment cancelled', ?int $userId = null): ?Refund
    {
        try {
            DB::beginTransaction();

            $payment = $appointment->payment;
            
            // If no payment exists, just cancel the appointment without refund processing
            if (!$payment) {
                // Update appointment status to cancelled
                $appointment->update([
                    'status' => Appointment::STATUS_CANCELLED
                ]);

                // Free up time slots
                $appointment->manageTimeSlots(true);

                DB::commit();

                Log::info('Appointment cancelled without payment/refund processing', [
                    'appointment_id' => $appointment->id,
                    'reason' => $reason,
                    'cancelled_by' => $userId
                ]);

                return null; // No refund record created
            }

            // Calculate refund amounts
            $refundCalculation = $this->calculateRefund($appointment);

            // Create refund record
            $refund = Refund::create([
                'appointment_id' => $appointment->id,
                'payment_id' => $payment->id,
                'user_id' => $userId ?? $appointment->client_id,
                'provider_id' => $appointment->user_id,
                'refund_amount' => $refundCalculation['refund_amount'],
                'vendor_amount' => $refundCalculation['vendor_amount'],
                'admin_amount' => $refundCalculation['admin_amount'],
                'refund_type' => $refundCalculation['refund_type'],
                'refund_reason' => $reason,
                'refund_status' => Refund::STATUS_PENDING,
                'refund_reference' => 'REF_' . time() . '_' . rand(1000, 9999),
                'cancellation_time_hours' => $refundCalculation['cancellation_hours'],
                'original_amount' => $refundCalculation['original_amount'],
                'service_charges' => $refundCalculation['service_charges'],
                'platform_fee' => $refundCalculation['platform_fee'],
                'other_charges' => $refundCalculation['other_charges'],
                'gst_amount' => $refundCalculation['gst_amount'],
                'refund_details' => [
                    'policy_applied' => $refundCalculation['policy_applied'],
                    'calculation_breakdown' => $refundCalculation,
                    'processed_by' => $userId ? 'user' : 'system',
                    'processed_at' => now()->toIso8601String()
                ]
            ]);

            // Update appointment status
            $appointment->update([
                'status' => Appointment::STATUS_CANCELLED,
                'payment_status' => $refundCalculation['refund_amount'] > 0 
                    ? Appointment::PAYMENT_STATUS_REFUNDED 
                    : $appointment->payment_status
            ]);

            // Update payment status if refund is being processed
            if ($refundCalculation['refund_amount'] > 0) {
                $payment->update(['status' => Payment::STATUS_REFUNDED]);
            }

            // Free up time slots
            $appointment->manageTimeSlots(true);

            // Auto-process refund if amount > 0 (in real implementation, this would integrate with payment gateway)
            if ($refundCalculation['refund_amount'] > 0) {
                $this->processRefundPayment($refund);
            } else {
                // Mark as processed immediately for no-refund cases
                $refund->update([
                    'refund_status' => Refund::STATUS_PROCESSED,
                    'processed_at' => now()
                ]);
            }

            DB::commit();

            Log::info('Refund processed successfully', [
                'appointment_id' => $appointment->id,
                'refund_id' => $refund->id,
                'refund_amount' => $refundCalculation['refund_amount'],
                'refund_type' => $refundCalculation['refund_type']
            ]);

            return $refund;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process refund', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process the actual refund payment (integrate with payment gateway)
     *
     * @param Refund $refund
     * @return bool
     */
    private function processRefundPayment(Refund $refund): bool
    {
        try {
            // In a real implementation, this would integrate with your payment gateway
            // For now, we'll simulate the refund processing
            
            // Simulate payment gateway call
            $refundSuccess = $this->simulatePaymentGatewayRefund($refund);

            if ($refundSuccess) {
                $refund->update([
                    'refund_status' => Refund::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'refund_details' => array_merge($refund->refund_details ?? [], [
                        'gateway_response' => 'Refund processed successfully',
                        'gateway_reference' => 'GW_REF_' . time(),
                        'processed_at' => now()->toIso8601String()
                    ])
                ]);

                Log::info('Refund payment processed successfully', [
                    'refund_id' => $refund->id,
                    'amount' => $refund->refund_amount
                ]);

                return true;
            } else {
                $refund->update([
                    'refund_status' => Refund::STATUS_FAILED,
                    'refund_details' => array_merge($refund->refund_details ?? [], [
                        'gateway_response' => 'Refund processing failed',
                        'error_at' => now()->toIso8601String()
                    ])
                ]);

                Log::error('Refund payment processing failed', [
                    'refund_id' => $refund->id,
                    'amount' => $refund->refund_amount
                ]);

                return false;
            }

        } catch (\Exception $e) {
            $refund->update([
                'refund_status' => Refund::STATUS_FAILED,
                'refund_details' => array_merge($refund->refund_details ?? [], [
                    'error' => $e->getMessage(),
                    'error_at' => now()->toIso8601String()
                ])
            ]);

            Log::error('Exception during refund payment processing', [
                'refund_id' => $refund->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Simulate payment gateway refund (replace with actual gateway integration)
     *
     * @param Refund $refund
     * @return bool
     */
    private function simulatePaymentGatewayRefund(Refund $refund): bool
    {
        // Simulate 95% success rate for refunds
        return rand(1, 100) <= 95;
    }

    /**
     * Get refund policy details for a given appointment
     *
     * @param Appointment $appointment
     * @return array
     */
    public function getRefundPolicyDetails(Appointment $appointment): array
    {
        $appointmentDateTime = Carbon::parse($appointment->date->format('Y-m-d') . ' ' . $appointment->start_time->format('H:i:s'));
        $hoursUntilAppointment = Carbon::now()->diffInHours($appointmentDateTime, false);

        $policy = [
            'hours_until_appointment' => $hoursUntilAppointment,
            'can_cancel' => $appointment->canBeCancelled(),
            'policies' => [
                [
                    'condition' => 'Before 24 hours of booking time',
                    'refund_percentage' => 100,
                    'description' => 'Full refund of the total paid amount',
                    'applies' => $hoursUntilAppointment >= 24
                ],
                [
                    'condition' => 'Between 24 hours and 2 hours before booking time',
                    'refund_percentage' => 75,
                    'description' => '75% of Service Charges refunded. Vendor receives 15%, Admin receives 10%',
                    'applies' => $hoursUntilAppointment >= 2 && $hoursUntilAppointment < 24
                ],
                [
                    'condition' => 'Within 2 hours of booking time or after booking time',
                    'refund_percentage' => 0,
                    'description' => 'No refund. Vendor receives 50%, Admin receives 50% of Service Charges',
                    'applies' => $hoursUntilAppointment < 2
                ]
            ]
        ];

        // Add current applicable policy
        foreach ($policy['policies'] as $policyItem) {
            if ($policyItem['applies']) {
                $policy['current_policy'] = $policyItem;
                break;
            }
        }

        return $policy;
    }

    /**
     * Get refund history for a user
     *
     * @param int $userId
     * @param string|null $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserRefunds(int $userId, ?string $status = null)
    {
        $query = Refund::where('user_id', $userId)
            ->with(['appointment', 'payment', 'provider'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('refund_status', $status);
        }

        return $query->get();
    }

    /**
     * Get refund statistics for admin dashboard
     *
     * @param string|null $period
     * @return array
     */
    public function getRefundStatistics(?string $period = 'month'): array
    {
        $startDate = match($period) {
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth()
        };

        $refunds = Refund::where('created_at', '>=', $startDate)->get();

        return [
            'total_refunds' => $refunds->count(),
            'total_refund_amount' => $refunds->sum('refund_amount'),
            'pending_refunds' => $refunds->where('refund_status', Refund::STATUS_PENDING)->count(),
            'processed_refunds' => $refunds->where('refund_status', Refund::STATUS_PROCESSED)->count(),
            'failed_refunds' => $refunds->where('refund_status', Refund::STATUS_FAILED)->count(),
            'full_refunds' => $refunds->where('refund_type', Refund::TYPE_FULL_REFUND)->count(),
            'partial_refunds' => $refunds->where('refund_type', Refund::TYPE_PARTIAL_REFUND)->count(),
            'no_refunds' => $refunds->where('refund_type', Refund::TYPE_NO_REFUND)->count(),
            'average_refund_amount' => $refunds->where('refund_amount', '>', 0)->avg('refund_amount') ?? 0,
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d')
        ];
    }
}