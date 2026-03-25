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
        Schema::table('offers', function (Blueprint $table) {
            $table->decimal('discount_percentage', 8, 2)
                ->nullable()
                ->change();

            $table->decimal('discount_fixed', 10, 2)
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->decimal('discount_percentage', 8, 2)
                ->nullable(false)
                ->change();
        });
    }
};
