<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id')->index();
            $table->string('owner_type')->index();

            $table->text('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('media_type')->default('gallery');

            $table->integer('sort_number')->default(0);
            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });

        Schema::create('media_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medium_id')->constrained('media')->onDelete('cascade');
            $table->string('locale')->index();

            $table->string('title')->nullable();
            $table->text('alt')->nullable();
            $table->text('short_description')->nullable();

            $table->unique(['medium_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_translations');
        Schema::dropIfExists('media');
    }
};
