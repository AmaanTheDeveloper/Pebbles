<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('category');
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable();
            $table->string('priority')->default('normal');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('related');
            $table->json('data')->nullable();
            $table->string('action_url')->nullable();
            $table->string('dedupe_key')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['recipient_user_id', 'dedupe_key']);
            $table->index(['recipient_user_id', 'archived_at', 'read_at']);
            $table->index(['recipient_user_id', 'created_at']);
            $table->index(['recipient_user_id', 'category']);
            $table->index(['branch_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
