<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Add combo_service_id column after service_id
            $table->foreignId('combo_service_id')->nullable()->after('service_id')->constrained()->nullOnDelete();

            // Make service_id nullable because an appointment could be for a combo service instead
            $table->foreignId('service_id')->nullable()->change();

            // Add new columns for original price, discount, and final price
            $table->decimal('original_price', 10, 2)->nullable()->after('notes');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('original_price');
            $table->decimal('final_price', 10, 2)->nullable()->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['combo_service_id']);

            // Drop columns
            $table->dropColumn('combo_service_id');
            $table->dropColumn('original_price');
            $table->dropColumn('discount_amount');
            $table->dropColumn('final_price');

            // Make service_id required again
            $table->foreignId('service_id')->nullable(false)->change();
        });
    }
};
