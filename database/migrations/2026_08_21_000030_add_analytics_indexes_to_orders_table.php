<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['created_at', 'status']);
            $table->index(['created_at', 'branch_id']);
            $table->index(['created_at', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['created_at', 'status']);
            $table->dropIndex(['created_at', 'branch_id']);
            $table->dropIndex(['created_at', 'type']);
        });
    }
};
