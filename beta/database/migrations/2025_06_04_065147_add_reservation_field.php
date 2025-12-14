<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->timestamp('reservation_expires_at')->nullable()->after('is_available');
            $table->unsignedBigInteger('reserved_for_appointment_id')->nullable()->after('reservation_expires_at');

            $table->foreign('reserved_for_appointment_id')->references('id')->on('appointments')->onDelete('set null');
            $table->index(['reservation_expires_at']);
            $table->index(['reserved_for_appointment_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('payment_timeout')->nullable()->after('payment_status');
            $table->string('booking_status')->default('pending_payment')->after('status');

            // Add index with custom shorter name
            $table->index(['booking_status', 'payment_timeout'], 'idx_appointments_booking');
        });
    }

    public function down()
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->dropForeign(['reserved_for_appointment_id']);
            $table->dropColumn(['reservation_expires_at', 'reserved_for_appointment_id']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_booking');
            $table->dropColumn(['payment_timeout', 'booking_status']);
        });
    }
};
