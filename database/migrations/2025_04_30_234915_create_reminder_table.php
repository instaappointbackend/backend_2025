<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->date('reminder_date');
            $table->string('reminder_time');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->enum('type', ['one-time', 'recurring'])->default('one-time');
            $table->enum('recurrence_pattern', ['daily', 'weekly', 'monthly'])->nullable();
            $table->date('recurrence_end_date')->nullable();
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            // Optional relationships
            $table->foreignId('target_id')->nullable();
            $table->string('target_type')->nullable();

            // Tracking fields
            $table->boolean('is_read')->default(false);
            $table->boolean('notification_sent')->default(false);

            $table->timestamps();

            // Add indexes for common queries
            $table->index('reminder_date');
            $table->index('status');
            $table->index('priority');
            $table->index(['target_id', 'target_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminders');
    }
};
