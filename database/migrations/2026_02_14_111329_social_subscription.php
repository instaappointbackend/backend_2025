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
        Schema::create('social_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('plan_name');
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'expired'])->default('pending');
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_notification_sent_at')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });

        Schema::table('social_subscriptions', function (Blueprint $table) {
            $table->string('payment_method', 100)
                ->nullable()
                ->after('response');
        });

        // Schema::table('social_subscriptions', function (Blueprint $table) {
        //     $table->renameColumn('phonepe_transaction_id', 'transaction_id');
        // });

        Schema::table('social_subscriptions', function (Blueprint $table) {
            $table->string('razorpay_payment_id', 100)
                ->nullable()
                ->after('transaction_id');

            $table->string('payment_gateway', 100)
                ->nullable()
                ->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_subscriptions');
    }
};
