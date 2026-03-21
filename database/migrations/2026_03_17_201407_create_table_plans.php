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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->integer('original_price')->nullable();
            $table->integer('discounted_price');
            $table->string('discount')->nullable();
            $table->string('duration')->nullable();
            $table->string('badge')->nullable();
            $table->string('tagline')->nullable();
            $table->boolean('highlight')->default(false);
            $table->string('button_text')->nullable();
            $table->string('button_class')->nullable();
            $table->string('border_class')->nullable();
            $table->string('type'); // "normal" or "social"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_plans');
    }
};
