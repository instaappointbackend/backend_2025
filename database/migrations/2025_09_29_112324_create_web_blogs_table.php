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
        Schema::create('web_blogs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('sub_title');
            $table->text('style')->nullable();
            $table->text('description');
            $table->unsignedBigInteger('user_id'); // admin/creator
            $table->unsignedBigInteger('category_id');
            $table->string('image_path');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('read_count')->default(0);

            $table->timestamps();
        });

        Schema::create('web_blog_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blog_id');
            $table->string('image_path');
            $table->text('image_description')->nullable();
            $table->timestamps();
            $table->foreign('blog_id')->references('id')->on('web_blogs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('web_blogs');
        Schema::dropIfExists('blog_images');
    }
};
