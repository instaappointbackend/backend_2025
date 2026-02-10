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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Customer who gets refund
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade'); // Service provider

            // Refund amounts
            $table->decimal('refund_amount', 10, 2)->default(0); // Amount refunded to customer
            $table->decimal('vendor_amount', 10, 2)->default(0); // Amount vendor receives
            $table->decimal('admin_amount', 10, 2)->default(0); // Amount admin receives

            // Refund details
            $table->enum('refund_type', ['full_refund', 'partial_refund', 'no_refund']);
            $table->string('refund_reason')->nullable();
            $table->enum('refund_status', ['pending', 'processed', 'failed', 'cancelled'])->default('pending');
            $table->string('refund_reference')->unique();
            $table->timestamp('processed_at')->nullable();
            $table->json('refund_details')->nullable();

            // Policy calculation details
            $table->decimal('cancellation_time_hours', 8, 2)->nullable(); // Hours before appointment when cancelled
            $table->decimal('original_amount', 10, 2); // Original payment amount
            $table->decimal('service_charges', 10, 2)->default(0); // Service charges from original payment
            $table->decimal('platform_fee', 10, 2)->default(0); // Platform fee from original payment
            $table->decimal('other_charges', 10, 2)->default(0); // Other charges from original payment
            $table->decimal('gst_amount', 10, 2)->default(0); // GST amount from original payment

            $table->timestamps();

            // Indexes
            $table->index(['appointment_id', 'refund_status']);
            $table->index(['user_id', 'refund_status']);
            $table->index(['provider_id', 'refund_status']);
            $table->index('refund_reference');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
