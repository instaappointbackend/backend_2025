<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Create time_slots table
return new class extends Migration
{
    public function up()
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            
            // Each user can only have one time slot per date/time combination
            $table->unique(['user_id', 'date', 'start_time', 'end_time']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('time_slots');
    }
};