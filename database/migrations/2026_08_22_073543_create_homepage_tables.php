<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->unsignedInteger('display_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('homepage_features', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('icon_letter', 2)->default('P');
            $table->string('accent')->default('from-brand-green/15 to-brand-green/5');
            $table->string('glow')->default('lift-card-green');
            $table->unsignedInteger('display_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('homepage_cta_cards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('link_url');
            $table->string('tone')->default('orange');
            $table->unsignedInteger('display_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('homepage_showcase_items', function (Blueprint $table) {
            $table->id();
            $table->string('pillar_key');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->text('blurb')->nullable();
            $table->string('badge')->nullable();
            $table->string('accent')->default('orange');
            $table->boolean('is_special')->default(false);
            $table->unsignedInteger('display_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_showcase_items');
        Schema::dropIfExists('homepage_cta_cards');
        Schema::dropIfExists('homepage_features');
        Schema::dropIfExists('homepage_sections');
    }
};
