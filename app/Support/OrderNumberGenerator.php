<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderNumberGenerator
{
    /**
     * Generate a unique public order number such as PB-10234.
     */
    public function generate(): string
    {
        return DB::transaction(function (): string {
            for ($attempt = 0; $attempt < 25; $attempt++) {
                $number = 'PB-'.random_int(10000, 99999);

                if (! Order::query()->where('public_order_number', $number)->exists()) {
                    return $number;
                }
            }

            return 'PB-'.Str::upper(Str::random(8));
        });
    }
}
