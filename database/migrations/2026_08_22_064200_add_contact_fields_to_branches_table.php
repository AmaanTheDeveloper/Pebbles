<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('map_url')->nullable()->after('longitude');
            $table->string('image_path')->nullable()->after('map_url');
            $table->json('opening_hours')->nullable()->after('closes_at');
            $table->unsignedInteger('display_order')->default(0)->index()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp',
                'map_url',
                'image_path',
                'opening_hours',
                'display_order',
            ]);
        });
    }
};
