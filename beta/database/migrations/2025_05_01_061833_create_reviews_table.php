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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('provider_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('service_id')->nullable()->references('id')->on('services')->onDelete('set null');
            $table->foreignId('combo_service_id')->nullable()->references('id')->on('combo_services')->onDelete('set null');
            $table->decimal('rating', 3, 1);
            $table->text('review_text')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('review_response')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('provider_id');
            $table->index('appointment_id');
            $table->index('service_id');
            $table->index('combo_service_id');
            $table->index('status');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};
