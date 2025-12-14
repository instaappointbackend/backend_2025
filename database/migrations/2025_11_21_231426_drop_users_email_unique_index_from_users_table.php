<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop index only if it exists
            // Check if index exists before dropping
            $indexExists = DB::select("
            SELECT COUNT(1) AS count
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'users'
              AND index_name = 'users_email_unique'
        ")[0]->count;

            if ($indexExists) {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique('users_email_unique');
                });
            }
        });
    }

    public function down(): void
    {
        // Schema::table('users', function (Blueprint $table) {
        //     // Restore the unique constraint
        //     $table->unique('email', 'users_email_unique');
        // });
    }
};
