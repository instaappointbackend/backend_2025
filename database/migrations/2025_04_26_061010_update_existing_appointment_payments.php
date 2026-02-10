<?php

use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        // Process all existing payments for appointments
        $this->updateAppointmentPayments();
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        // Nothing to reverse
    }

    /**
     * Update all existing payments created through appointments to include vendor and admin earnings.
     */
    private function updateAppointmentPayments()
    {
        // Get all payments related to appointments
        $payments = Payment::whereNotNull('appointment_id')->get();

        // Process each payment
        foreach ($payments as $payment) {
            // Get related appointment
            $appointment = Appointment::find($payment->appointment_id);

            if (! $appointment) {
                continue; // Skip if appointment not found
            }

            // Get payment fields from appointment if not set in payment
            $bookingPrice = $payment->booking_price ?? $appointment->booking_price ?? 0;
            $homeVisitFee = $payment->home_visit_fee ?? $appointment->home_visit_fee ?? 0;
            $discountAmount = $payment->discount_amount ?? $appointment->discount_amount ?? 0;
            $platformFee = $payment->platform_fee ?? $appointment->platform_fees ?? 8.00;
            $otherCharges = $payment->other_charges ?? $appointment->other_charges ??
                round($bookingPrice * 0.02, 2);
            $gstAmount = $payment->gst_amount ?? $appointment->gst ??
                round(($platformFee + $otherCharges) * 0.18, 2);

            // Calculate vendor earnings (booking price + home visit fee - discount)
            $vendorEarnings = $bookingPrice + $homeVisitFee - $discountAmount;

            // Ensure vendor earnings is never negative
            $vendorEarnings = max(0, $vendorEarnings);

            // Calculate admin earnings (platform fee + other charges + GST)
            $adminEarnings = $platformFee + $otherCharges + $gstAmount;

            // Update payment record with calculated earnings
            DB::table('payments')
                ->where('id', $payment->id)
                ->update([
                    'vendor_earnings' => $vendorEarnings,
                    'admin_earnings' => $adminEarnings,
                    'updated_at' => now(),
                ]);
        }
    }
};
