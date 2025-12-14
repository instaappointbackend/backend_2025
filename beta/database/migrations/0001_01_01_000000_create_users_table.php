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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->text('password');
            $table->string('mobile')->unique(); // Mobile is required and unique
            $table->rememberToken();
            $table->unsignedBigInteger('created_by')->default(0);
            $table->string('otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('profile_picture')->nullable();
            $table->date('dob')->nullable();
            $table->enum('role', ['admin', 'customer', 'vendor'])->default('customer');
            $table->boolean('status')->default(1);
            $table->string('reference_code')->nullable();
            $table->foreignId('reference_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->boolean('is_kyc_completed')->default(0);

            $table->index('email'); // Indexing for faster queries
            $table->index('mobile'); // Indexing for faster queries
            $table->timestamps();
            $table->softDeletes(); // Allows soft deleting users
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users'); // Only drop users table
    }
};
