<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // For MySQL, we need to modify the enum
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending_payment', 'pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending_payment'");
        } else {
            // For PostgreSQL, you might need to handle this differently
            // This is a simplified approach - in production, consider using a separate lookup table
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('status')->default('pending_payment')->change();
            });
        }
    }

    public function down()
    {
        // Revert to original enum values
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE appointments MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending'");
        } else {
            Schema::table('appointments', function (Blueprint $table) {
                $table->string('status')->default('pending')->change();
            });
        }
    }
};
