<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('recipient_name');
            $table->string('phone');
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('area');
            $table->string('city');
            $table->text('instructions')->nullable();
            $table->foreignId('delivery_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
