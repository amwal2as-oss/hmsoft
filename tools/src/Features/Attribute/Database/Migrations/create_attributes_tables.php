<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 100)->index();
            $table->string('image')->nullable();
            $table->string('type', 50);
            $table->string('cast_type', 50)->default('string');
            $table->json('category_ids')->nullable();
            $table->integer('sort_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        Schema::create('attribute_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('title');
            $table->unique(['attribute_id', 'locale']);
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->integer('sort_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attribute_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_option_id')->constrained('attribute_options')->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('title');
            $table->unique(['attribute_option_id', 'locale'], 'attr_opt_locale_unique');
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->string('owner_id', 50);
            $table->string('owner_type', 255);
            $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->string('locale', 10)->nullable();
            $table->text('value')->nullable();

            $table->index(['owner_type', 'owner_id', 'attribute_id'], 'idx_attr_values_owner');
            $table->index(DB::raw('attribute_id, value(50)'), 'idx_attr_values_search');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attribute_option_translations');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attribute_translations');
        Schema::dropIfExists('attributes');
    }
};
