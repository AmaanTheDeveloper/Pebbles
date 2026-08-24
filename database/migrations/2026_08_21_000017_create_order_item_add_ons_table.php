<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('add_on_id')->nullable()->constrained()->nullOnDelete();
            $table->string('add_on_name');
            $table->decimal('unit_price', 10, 2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_add_ons');
    }
};
