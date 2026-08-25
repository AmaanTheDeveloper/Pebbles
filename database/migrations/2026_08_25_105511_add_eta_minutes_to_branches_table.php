<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedInteger('preparation_minutes')->default(20)->after('display_order');
            $table->unsignedInteger('delivery_minutes')->default(30)->after('preparation_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['preparation_minutes', 'delivery_minutes']);
        });
    }
};
