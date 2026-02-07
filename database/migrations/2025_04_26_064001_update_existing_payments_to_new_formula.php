<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Get all existing payments
        $payments = Payment::all();

        foreach ($payments as $payment) {
            // Set original price from existing data
            // If booking_price exists, use it as the original_price
            // as it likely represents the service price before calculations
            if ($payment->original_price === null) {
                if ($payment->booking_price !== null) {
                    // For existing records, if the booking_price already includes home visit fee,
                    // we need to subtract it to get original price
                    $originalPrice = $payment->booking_price;

                    // If there's a home visit fee, subtract it to get just the service price
                    if ($payment->home_visit_fee > 0) {
                        $originalPrice = $originalPrice - $payment->home_visit_fee;
                    }

                    // If there was a discount, add it back to get original price
                    if ($payment->discount_amount > 0) {
                        $originalPrice = $originalPrice + $payment->discount_amount;
                    }

                    $payment->original_price = $originalPrice;
                } elseif ($payment->appointment) {
                    // If there's an appointment with service price
                    $payment->original_price = $payment->appointment->service->price ?? 0;
                } else {
                    // Fallback - use a calculated value from amount
                    // This is an approximation and may not be accurate for all cases
                    $adminFees = ($payment->platform_fee ?? 8) +
                        ($payment->other_charges ?? 0) +
                        ($payment->gst_amount ?? 0);
                    $payment->original_price = $payment->amount - $adminFees;
                }
            }

            // Now recalculate everything using the new formula
            $homeVisitFee = $payment->home_visit_fee ?? 0;
            $discountAmount = $payment->discount_amount ?? 0;

            // Platform fee is fixed at 8 Rs
            $platformFee = 8.00;

            // Calculate booking price (Vendor Earnings)
            $bookingPrice = $payment->original_price + $homeVisitFee - $discountAmount;

            // Ensure booking price is not negative
            $bookingPrice = max(0, $bookingPrice);

            // Calculate Other Charges (2% of Booking Price)
            $otherCharges = round($bookingPrice * 0.02, 2);

            // Calculate GST (18% of Platform Fee + Other Charges)
            $gstAmount = round(($platformFee + $otherCharges) * 0.18, 2);

            // Calculate Admin Earnings
            $adminEarnings = $platformFee + $otherCharges + $gstAmount;

            // Calculate total amount
            $totalAmount = $bookingPrice + $adminEarnings;

            // Update the payment record
            $payment->booking_price = $bookingPrice;
            $payment->platform_fee = $platformFee;
            $payment->other_charges = $otherCharges;
            $payment->gst_amount = $gstAmount;
            $payment->vendor_earnings = $bookingPrice;
            $payment->admin_earnings = $adminEarnings;

            // Only update the amount if the payment is still pending
            // For completed transactions, we should preserve the original amount
            if ($payment->status === 'pending') {
                $payment->amount = $totalAmount;
                $payment->net_amount = $totalAmount;
            }

            $payment->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
