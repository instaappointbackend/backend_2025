<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Create working_hours table
return new class extends Migration
{
    public function up()
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('day_of_week'); // 0 = Sunday, 1 = Monday, etc.
            $table->boolean('is_working_day')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->timestamps();

            // Each user can only have one record per day of week
            $table->unique(['user_id', 'day_of_week']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('working_hours');
    }
};
