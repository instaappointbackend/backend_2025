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
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');

            // Refund amounts
            $table->decimal('refund_amount', 10, 2);
            $table->decimal('customer_refund', 10, 2);
            $table->decimal('vendor_compensation', 10, 2);
            $table->decimal('admin_retention', 10, 2);

            // Policy and timing information
            $table->enum('policy_tier', ['full_refund', 'partial_refund', 'no_refund']);
            $table->timestamp('cancellation_time');
            $table->timestamp('booking_time')->nullable();
            $table->decimal('hours_before_booking', 8, 2);

            // Refund details
            $table->string('refund_reason');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])
                ->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->json('refund_breakdown')->nullable();
            $table->string('gateway_refund_id')->nullable();

            // Audit fields
            $table->foreignId('initiated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->json('gateway_response')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['payment_id', 'status']);
            $table->index(['appointment_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['policy_tier', 'status']);
            $table->index('processed_at');
            $table->index('cancellation_time');
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
