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
        Schema::create('appointment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->onDelete('cascade');
            $table->integer('reminder_minutes'); // 1440, 300, 60, 30, 5
            $table->enum('recipient_type', ['client', 'provider']);
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // recipient user
            $table->timestamp('sent_at');
            $table->json('notification_data')->nullable(); // store notification payload
            $table->boolean('delivery_success')->default(true);
            $table->text('delivery_error')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['appointment_id', 'reminder_minutes']);
            $table->index(['sent_at']);
            $table->unique(['appointment_id', 'reminder_minutes', 'recipient_type', 'user_id'], 'unique_reminder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_reminders');
    }
};
