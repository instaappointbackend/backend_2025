<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointment_settings', function (Blueprint $table) {
            $table->json('payment_methods')->nullable()->after('appointment_modes');
        });

        // Add default payment methods to existing records
        $defaultPaymentMethods = json_encode([
            'phonepe' => true,
            'cash' => true,
            'card' => false,
            'upi' => false,
        ]);

        DB::table('appointment_settings')
            ->whereNull('payment_methods')
            ->update(['payment_methods' => $defaultPaymentMethods]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_settings', function (Blueprint $table) {
            $table->dropColumn('payment_methods');
        });
    }
};
