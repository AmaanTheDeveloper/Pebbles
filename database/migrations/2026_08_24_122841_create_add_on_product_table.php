<?php

use App\Models\AddOn;
use App\Models\Product;
use App\Support\ProductAddOnRules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('add_on_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('add_on_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['add_on_id', 'product_id']);
            $table->index('product_id');
        });

        $this->backfillExistingAssignments();
    }

    public function down(): void
    {
        Schema::dropIfExists('add_on_product');
    }

    /**
     * Preserve current category/group availability by assigning each existing
     * add-on to every product that already qualifies under ProductAddOnRules.
     */
    private function backfillExistingAssignments(): void
    {
        $now = now();

        AddOn::query()
            ->with('group')
            ->orderBy('id')
            ->chunkById(100, function ($addOns) use ($now): void {
                foreach ($addOns as $addOn) {
                    /** @var AddOn $addOn */
                    $groupSlug = $addOn->group?->slug;
                    $categorySlugs = ProductAddOnRules::categorySlugsForGroup($groupSlug);

                    if ($categorySlugs === []) {
                        continue;
                    }

                    $productIds = Product::query()
                        ->whereHas('category', fn ($query) => $query->whereIn('slug', $categorySlugs))
                        ->pluck('id');

                    if ($productIds->isEmpty()) {
                        continue;
                    }

                    $rows = $productIds->map(fn (int $productId): array => [
                        'add_on_id' => $addOn->id,
                        'product_id' => $productId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    DB::table('add_on_product')->insertOrIgnore($rows);
                }
            });
    }
};
