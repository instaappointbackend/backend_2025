<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Appointment;
use Carbon\Carbon;

class PaymentService
{
    /**
     * Calculate payment breakdown according to the formula.
     *
     * Vendor earnings = Original price + Home visit fee - Discount
     * Admin earnings = Platform fee + Other charges + GST
     * Platform fee = Fixed 8 Rs
     * Other charges = 2% of Booking Price (which is Original price + Home visit fee - Discount)
     * GST = 18% of (Platform fee + Other charges)
     *
     * @param  array  $data Input data containing amounts
     * @return array Calculated payment breakdown
     */
    public function calculatePaymentBreakdown($data)
    {
        // Get required values with fallbacks
        $originalPrice = isset($data['original_price']) ? floatval($data['original_price']) : 0;
        $homeVisitFee = isset($data['home_visit_fee']) ? floatval($data['home_visit_fee']) : 0;
        $discountAmount = isset($data['discount_amount']) ? floatval($data['discount_amount']) : 0;

        // Platform fee is fixed at 8 Rs
        $platformFee = 8.00;

        // Calculate booking price (Vendor Earnings)
        $bookingPrice = $originalPrice + $homeVisitFee - $discountAmount;

        // Ensure booking price is not negative
        $bookingPrice = max(0, $bookingPrice);

        // Calculate Other Charges (2% of Booking Price)
        $otherCharges = round($bookingPrice * 0.02, 2);

        // Calculate GST (18% of Platform Fee + Other Charges)
        $gstAmount = round(($platformFee + $otherCharges) * 0.18, 2);

        // Calculate Admin Earnings
        $adminEarnings = $platformFee + $otherCharges + $gstAmount;

        // Calculate total amount customer pays
        $totalAmount = $bookingPrice + $adminEarnings;

        // Return calculated values
        return [
            'original_price' => $originalPrice,
            'home_visit_fee' => $homeVisitFee,
            'discount_amount' => $discountAmount,
            'booking_price' => $bookingPrice,
            'platform_fee' => $platformFee,
            'other_charges' => $otherCharges,
            'gst_amount' => $gstAmount,
            'admin_earnings' => $adminEarnings,
            'vendor_earnings' => $bookingPrice,
            'amount' => $totalAmount,
            'net_amount' => $totalAmount,
            'final_price' => $totalAmount
        ];
    }

    /**
     * Generate payment statistics for the dashboard.
     *
     * @param  string  $period 'today', 'week', 'month', 'year'
     * @return array
     */
    public function getPaymentStats($period = 'month')
    {
        // Determine date range based on period
        $endDate = Carbon::now();

        switch ($period) {
            case 'today':
                $startDate = Carbon::today();
                break;
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth();
                break;
            case 'year':
                $startDate = Carbon::now()->startOfYear();
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
        }

        // Get payment statistics
        $totalPayments = Payment::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalAmount = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount');

        $platformFees = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->sum('platform_fee');

        $otherCharges = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->sum('other_charges');

        $gstAmount = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->sum('gst_amount');

        $adminEarnings = $platformFees + $otherCharges + $gstAmount;

        $vendorEarnings = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->sum('vendor_earnings');

        if ($vendorEarnings == 0) {
            $vendorEarnings = Payment::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', Payment::STATUS_PAID)
                ->sum('booking_price');
        }

        // Get payment counts by status
        $pendingCount = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PENDING)
            ->count();

        $paidCount = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_PAID)
            ->count();

        $failedCount = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_FAILED)
            ->count();

        $refundedCount = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Payment::STATUS_REFUNDED)
            ->count();

        // Get previous period statistics for comparison
        $previousStartDate = (clone $startDate)->subDays($endDate->diffInDays($startDate) + 1);
        $previousEndDate = (clone $startDate)->subDay();

        $previousTotalAmount = Payment::whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount');

        // Calculate growth percentages
        $revenueGrowth = $previousTotalAmount > 0
            ? (($totalAmount - $previousTotalAmount) / $previousTotalAmount) * 100
            : 0;

        // Prepare the response
        $stats = [
            'period' => $period,
            'date_range' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'formatted_range' => $startDate->format('M d, Y') . ' - ' . $endDate->format('M d, Y')
            ],
            'revenue' => [
                'total' => $totalAmount,
                'formatted_total' => '₹' . number_format($totalAmount, 2),
                'growth' => $revenueGrowth,
                'growth_formatted' => number_format($revenueGrowth, 1) . '%',
                'is_positive' => $revenueGrowth >= 0
            ],
            'earnings' => [
                'admin' => $adminEarnings,
                'formatted_admin' => '₹' . number_format($adminEarnings, 2),
                'vendor' => $vendorEarnings,
                'formatted_vendor' => '₹' . number_format($vendorEarnings, 2)
            ],
            'counts' => [
                'total' => $totalPayments,
                'pending' => $pendingCount,
                'paid' => $paidCount,
                'failed' => $failedCount,
                'refunded' => $refundedCount
            ]
        ];

        return $stats;
    }

    /**
     * Process a refund for a payment.
     *
     * @param  Payment  $payment
     * @param  float  $amount
     * @param  string  $reason
     * @param  string  $initiatedBy
     * @return Payment
     */
    public function processRefund(Payment $payment, $amount, $reason, $initiatedBy = 'admin')
    {
        // Validate payment can be refunded
        if ($payment->status !== Payment::STATUS_PAID) {
            throw new \Exception('Only paid payments can be refunded.');
        }

        // Validate refund amount
        if ($amount > $payment->amount) {
            throw new \Exception('Refund amount cannot exceed the original payment amount.');
        }

        // Generate refund ID
        $refundId = 'REF_' . uniqid();

        // Create refund details
        $refundDetails = [
            'refund' => [
                'refund_id' => $refundId,
                'amount' => $amount,
                'reason' => $reason,
                'initiated_by' => $initiatedBy,
                'initiated_at' => now()->toIso8601String(),
                'status' => 'completed'
            ]
        ];

        // Merge with existing payment details
        $paymentDetails = is_array($payment->payment_details)
            ? $payment->payment_details
            : json_decode($payment->payment_details ?? '{}', true) ?? [];

        $updatedPaymentDetails = array_merge($paymentDetails, $refundDetails);

        // Update payment
        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'payment_details' => $updatedPaymentDetails
        ]);

        // Update appointment payment status
        if ($payment->appointment) {
            $payment->appointment->update([
                'payment_status' => Payment::STATUS_REFUNDED
            ]);
        }

        return $payment;
    }

    /**
     * Get recent payments for dashboard widget.
     *
     * @param  int  $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentPayments($limit = 5)
    {
        return Payment::with(['user', 'provider', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
