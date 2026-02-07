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
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Personal Identification
            $table->string('aadhar_number')->nullable();
            $table->string('aadhar_attachment')->nullable();
            $table->boolean('is_aadhar_verified')->nullable(false);

            $table->string('pan_number')->nullable();
            $table->string('pan_attachment')->nullable();
            $table->boolean('is_pan_verified')->nullable(false);

            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('bank_attachment')->nullable();
            $table->boolean('is_bank_verified')->nullable(false);

            // Business Details
            $table->string('business_name')->nullable();
            $table->text('business_address')->nullable();
            $table->string('business_type')->nullable();
            $table->text('description')->nullable();
            $table->string('business_logo')->nullable();
            $table->string('identity_document')->nullable();
            $table->boolean('is_business_verified')->nullable(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kyc_documents');
    }
};
