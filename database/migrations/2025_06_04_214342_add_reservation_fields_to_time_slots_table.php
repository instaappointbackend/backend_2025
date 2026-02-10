<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('time_slots', function (Blueprint $table) {
            // Add reservation-related fields
            $table->timestamp('reservation_expires_at')->nullable()->after('is_available');
            $table->unsignedBigInteger('reserved_for_appointment_id')->nullable()->after('reservation_expires_at');
            $table->string('reservation_token')->nullable()->after('reserved_for_appointment_id');

            // Add index for performance
            $table->index(['reserved_for_appointment_id']);
            $table->index(['reservation_expires_at']);
            $table->index(['reservation_token']);

            // Add foreign key constraint
            $table->foreign('reserved_for_appointment_id')
                ->references('id')
                ->on('appointments')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->dropForeign(['reserved_for_appointment_id']);
            $table->dropIndex(['reserved_for_appointment_id']);
            $table->dropIndex(['reservation_expires_at']);
            $table->dropIndex(['reservation_token']);
            $table->dropColumn([
                'reservation_expires_at',
                'reserved_for_appointment_id',
                'reservation_token',
            ]);
        });
    }
};
