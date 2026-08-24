<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('status');
            $table->unsignedSmallInteger('change_window_minutes')->nullable()->after('estimated_minutes');
        });

        $confirmations = DB::table('order_status_histories')
            ->select('order_id', DB::raw('MIN(created_at) as confirmed_at'))
            ->where('to_status', OrderStatus::Confirmed->value)
            ->groupBy('order_id')
            ->get();

        foreach ($confirmations as $confirmation) {
            DB::table('orders')
                ->where('id', $confirmation->order_id)
                ->whereNull('confirmed_at')
                ->where('status', '!=', OrderStatus::Pending->value)
                ->update(['confirmed_at' => $confirmation->confirmed_at]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['confirmed_at', 'change_window_minutes']);
        });
    }
};
