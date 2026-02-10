<?php

use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Calculate payment using the formula
        $appointments = Appointment::with(['payment', 'service'])->get();

        foreach ($appointments as $appointment) {
            // Skip appointments without payment amount
            if (! $appointment->payment_amount) {
                continue;
            }

            // Set original price if not already set
            if (! $appointment->original_price) {
                if ($appointment->service) {
                    $appointment->original_price = $appointment->service->price;
                } elseif ($appointment->comboService) {
                    $appointment->original_price = $appointment->comboService->discounted_price ??
                        $appointment->comboService->total_price;
                } else {
                    // If no service info, use booking_price or payment_amount as fallback
                    $appointment->original_price = $appointment->booking_price ?? $appointment->payment_amount;
                }

                // Adjust for home visit fee and discount if applicable
                if ($appointment->home_visit_fee > 0 || $appointment->discount_amount > 0) {
                    $homeVisitFee = $appointment->home_visit_fee ?? 0;
                    $discountAmount = $appointment->discount_amount ?? 0;

                    // Calculate backwards from booking_price: original = booking + discount - home_visit
                    if ($appointment->booking_price) {
                        $appointment->original_price = $appointment->booking_price + $discountAmount - $homeVisitFee;
                    }
                }
            }

            // Calculate booking price (vendor earnings)
            $originalPrice = $appointment->original_price;
            $homeVisitFee = $appointment->home_visit_fee ?? 0;
            $discountAmount = $appointment->discount_amount ?? 0;

            // Calculate booking price (vendor earnings)
            $bookingPrice = $originalPrice + $homeVisitFee - $discountAmount;
            $bookingPrice = max(0, $bookingPrice); // Ensure not negative

            // Calculate platform fees, other charges, and GST
            $platformFee = 8.00; // Fixed
            $otherCharges = round($bookingPrice * 0.02, 2); // 2% of booking price
            $gstAmount = round(($platformFee + $otherCharges) * 0.18, 2); // 18% of (platform fee + other charges)

            // Calculate admin earnings
            $adminEarnings = $platformFee + $otherCharges + $gstAmount;

            // Calculate total amount
            $totalAmount = $bookingPrice + $adminEarnings;

            // Update appointment with calculated values
            $appointment->platform_fees = $platformFee;
            $appointment->other_charges = $otherCharges;
            $appointment->gst = $gstAmount;
            $appointment->booking_price = $bookingPrice;
            $appointment->final_price = $totalAmount;
            $appointment->payment_amount = $totalAmount;
            $appointment->save();

            // Update payment record if exists
            if ($appointment->payment) {
                $payment = $appointment->payment;
                $payment->original_price = $originalPrice;
                $payment->booking_price = $bookingPrice;
                $payment->platform_fee = $platformFee;
                $payment->other_charges = $otherCharges;
                $payment->gst_amount = $gstAmount;
                $payment->amount = $totalAmount;
                $payment->net_amount = $totalAmount;
                $payment->vendor_earnings = $bookingPrice;
                $payment->admin_earnings = $adminEarnings;
                $payment->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
