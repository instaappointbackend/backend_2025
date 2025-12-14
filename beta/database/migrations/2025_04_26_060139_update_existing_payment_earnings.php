<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up()
    {
        // First, make sure vendor_earnings and admin_earnings columns exist
        if (!Schema::hasColumn('payments', 'vendor_earnings')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('vendor_earnings', 10, 2)->nullable()->after('net_amount');
            });
        }

        if (!Schema::hasColumn('payments', 'admin_earnings')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('admin_earnings', 10, 2)->nullable()->after('vendor_earnings');
            });
        }

        // Now update all existing payments to recalculate earnings
        $this->updateExistingPayments();
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        // Nothing to reverse as we don't want to remove data
    }

    /**
     * Update all existing payments to calculate vendor and admin earnings.
     */
    private function updateExistingPayments()
    {
        // Process payments in batches to avoid memory issues
        Payment::chunk(100, function ($payments) {
            foreach ($payments as $payment) {
                // Calculate vendor earnings = booking price + home visit fee - discount
                $bookingPrice = $payment->booking_price ?? 0;
                $homeVisitFee = $payment->home_visit_fee ?? 0;
                $discountAmount = $payment->discount_amount ?? 0;

                $vendorEarnings = $bookingPrice + $homeVisitFee - $discountAmount;
                $vendorEarnings = max(0, $vendorEarnings); // Ensure it's not negative

                // Calculate admin earnings = platform fee + other charges + GST
                $platformFee = $payment->platform_fee ?? 8.00; // Default 8 Rs
                $otherCharges = $payment->other_charges ?? round($bookingPrice * 0.02, 2); // Default 2% of booking price
                $gstAmount = $payment->gst_amount ?? round(($platformFee + $otherCharges) * 0.18, 2); // Default 18% GST

                $adminEarnings = $platformFee + $otherCharges + $gstAmount;

                // Update payment record directly with DB to avoid model events
                DB::table('payments')
                    ->where('id', $payment->id)
                    ->update([
                        'vendor_earnings' => $vendorEarnings,
                        'admin_earnings' => $adminEarnings,
                        'updated_at' => now()
                    ]);
            }
        });
    }
};
