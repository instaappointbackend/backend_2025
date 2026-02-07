<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Add payment timeout and reservation fields
            $table->timestamp('payment_timeout')->nullable()->after('payment_amount');
            $table->string('reservation_token')->nullable()->after('payment_timeout');
            $table->text('cancellation_reason')->nullable()->after('reservation_token');

            // Add index for performance
            $table->index(['payment_timeout']);
            $table->index(['reservation_token']);
        });
    }

    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['payment_timeout']);
            $table->dropIndex(['reservation_token']);
            $table->dropColumn([
                'payment_timeout',
                'reservation_token',
                'cancellation_reason',
            ]);
        });
    }
};
