<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add business_category_id foreign key
            $table->foreignId('business_category_id')->nullable()->after('status')
                  ->constrained('business_types')->nullOnDelete();
            
            // Add experience field
            $table->string('experience')->nullable()->after('business_category_id');
            
            // Add terms_accepted field
            $table->boolean('terms_accepted')->default(false)->after('experience');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_category_id']);
            $table->dropColumn('business_category_id');
            $table->dropColumn('experience');
            $table->dropColumn('terms_accepted');
        });
    }
};