<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eav_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100);
            $table->string('code', 100)->nullable();
            $table->string('input_type', 50);
            $table->string('value_type', 50);
            $table->json('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_number')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_sortable')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['entity_type', 'code']);
            $table->index(['entity_type', 'is_active']);
        });

        Schema::create('eav_attribute_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('eav_attributes')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->timestamps();

            $table->unique(['attribute_id', 'locale']);
            $table->index('locale');
        });

        Schema::create('eav_attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('eav_attributes')->cascadeOnDelete();
            $table->string('code', 100)->nullable();
            $table->integer('sort_number')->default(0);
            $table->string('color', 20)->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['attribute_id', 'is_active']);
        });

        Schema::create('eav_attribute_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_option_id')->constrained('eav_attribute_options')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('label');
            $table->timestamps();

            $table->unique(['attribute_option_id', 'locale'], 'eav_opt_locale_unique');
            $table->index('locale');
        });

        Schema::create('eav_attribute_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained('eav_attributes')->cascadeOnDelete();
            $table->string('category_type', 100);
            $table->unsignedBigInteger('category_id');

            $table->unique(['attribute_id', 'category_type', 'category_id'], 'eav_attr_cat_unique');
            $table->index(['category_type', 'category_id']);
        });

        Schema::create('eav_values', function (Blueprint $table) {
            $table->id();
            $table->string('valuable_type', 100);
            $table->unsignedBigInteger('valuable_id');
            $table->foreignId('attribute_id')->constrained('eav_attributes')->cascadeOnDelete();

            $table->string('value_text', 500)->nullable();
            $table->text('value_long_text')->nullable();
            $table->decimal('value_number', 20, 6)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->foreignId('attribute_option_id')->nullable()->constrained('eav_attribute_options')->nullOnDelete();

            $table->timestamps();

            $table->unique(['valuable_type', 'valuable_id', 'attribute_id'], 'eav_values_unique');
            $table->index(['valuable_type', 'valuable_id']);
            $table->index(['attribute_id', 'value_number']);
            $table->index(['attribute_id', 'value_boolean']);
            $table->index(['attribute_id', 'value_date']);
            $table->index(['attribute_id', 'attribute_option_id']);
            $table->index('value_text');
        });

        Schema::create('eav_value_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('value_id')->constrained('eav_values')->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('value_text', 500)->nullable();
            $table->text('value_long_text')->nullable();
            $table->timestamps();

            $table->unique(['value_id', 'locale']);
            $table->index('locale');
        });

        Schema::create('eav_value_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('value_id')->constrained('eav_values')->cascadeOnDelete();
            $table->foreignId('attribute_option_id')->constrained('eav_attribute_options')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['value_id', 'attribute_option_id'], 'eav_value_opt_unique');
            $table->index('attribute_option_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eav_value_options');
        Schema::dropIfExists('eav_value_translations');
        Schema::dropIfExists('eav_values');
        Schema::dropIfExists('eav_attribute_categories');
        Schema::dropIfExists('eav_attribute_option_translations');
        Schema::dropIfExists('eav_attribute_options');
        Schema::dropIfExists('eav_attribute_translations');
        Schema::dropIfExists('eav_attributes');
    }
};
