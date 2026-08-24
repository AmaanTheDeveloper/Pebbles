<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('public_order_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('type')->index();
            $table->string('status')->default('pending')->index();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->text('customer_notes')->nullable();

            $table->foreignId('delivery_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('delivery_zone_name')->nullable();
            $table->string('delivery_recipient_name')->nullable();
            $table->string('delivery_phone')->nullable();
            $table->string('delivery_line1')->nullable();
            $table->string('delivery_line2')->nullable();
            $table->string('delivery_area')->nullable();
            $table->string('delivery_city')->nullable();
            $table->text('delivery_instructions')->nullable();

            $table->string('table_number')->nullable();
            $table->text('pickup_notes')->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
