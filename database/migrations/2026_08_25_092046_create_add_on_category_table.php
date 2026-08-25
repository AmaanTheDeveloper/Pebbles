<?php

use App\Models\AddOn;
use App\Models\Category;
use App\Support\ProductAddOnRules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('add_on_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('add_on_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['add_on_id', 'category_id']);
            $table->index('category_id');
        });

        $this->backfillFromProductAssignments();
    }

    public function down(): void
    {
        Schema::dropIfExists('add_on_category');
    }

    private function backfillFromProductAssignments(): void
    {
        if (! Schema::hasTable('add_on_product')) {
            return;
        }

        $now = now();

        AddOn::query()
            ->with(['group', 'products.category'])
            ->orderBy('id')
            ->chunkById(100, function ($addOns) use ($now): void {
                foreach ($addOns as $addOn) {
                    /** @var AddOn $addOn */
                    $categoryIds = $addOn->products
                        ->pluck('category_id')
                        ->filter()
                        ->unique()
                        ->values();

                    if ($categoryIds->isEmpty()) {
                        $groupSlug = $addOn->group?->slug;
                        $categorySlugs = ProductAddOnRules::categorySlugsForGroup($groupSlug);

                        if ($categorySlugs === []) {
                            continue;
                        }

                        $categoryIds = Category::query()
                            ->whereIn('slug', $categorySlugs)
                            ->pluck('id');
                    }

                    if ($categoryIds->isEmpty()) {
                        continue;
                    }

                    $rows = $categoryIds->map(fn (int $categoryId): array => [
                        'add_on_id' => $addOn->id,
                        'category_id' => $categoryId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    DB::table('add_on_category')->insertOrIgnore($rows);
                }
            });
    }
};
