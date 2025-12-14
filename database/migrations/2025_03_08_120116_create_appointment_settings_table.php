<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Create appointment_settings table
return new class extends Migration
{
    public function up()
    {
        Schema::create('appointment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('appointment_duration')->default(60)->comment('in minutes');
            $table->integer('buffer_time')->default(15)->comment('in minutes');
            $table->integer('advance_booking_days')->default(30);
            $table->integer('max_bookings_per_day')->default(10);
            $table->boolean('is_online_booking_enabled')->default(true);
            $table->boolean('auto_confirm_appointments')->default(false);
            $table->timestamps();
            
            // Each user can only have one settings record
            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('appointment_settings');
    }
};