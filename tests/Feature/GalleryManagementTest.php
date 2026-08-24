<?php

use App\Enums\GalleryCategory;
use App\Models\GalleryItem;
use App\Models\User;
use App\Support\GalleryContentImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('public gallery api returns only active items ordered by display order', function () {
    GalleryItem::factory()->create([
        'title' => 'Hidden Shake',
        'category' => GalleryCategory::Food,
        'display_order' => 1,
        'is_active' => false,
    ]);

    GalleryItem::factory()->create([
        'title' => 'Visible Brownie',
        'category' => GalleryCategory::Food,
        'display_order' => 2,
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/catalog/gallery')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Visible Brownie')
        ->assertJsonPath('data.0.category', 'Food');
});

test('superadmin can manage gallery items', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $file = UploadedFile::fake()->image('shake.jpg');

    $create = $this->actingAs($admin)
        ->post('/api/v1/admin/gallery', [
            'title' => 'Chocolate Shake',
            'description' => 'A creamy chocolate shake.',
            'category' => GalleryCategory::Food->value,
            'display_order' => 3,
            'is_active' => true,
            'image' => $file,
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Chocolate Shake');

    $itemId = $create->json('data.id');

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/gallery/{$itemId}/status", ['is_active' => false])
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $this->getJson('/api/v1/catalog/gallery')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/gallery/{$itemId}/status", ['is_active' => true])
        ->assertSuccessful();

    $this->getJson('/api/v1/catalog/gallery')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Chocolate Shake');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/gallery/{$itemId}")
        ->assertSuccessful();
});

test('gallery legacy importer preserves placeholder interior items', function () {
    $created = app(GalleryContentImporter::class)->importIfEmpty();

    expect($created)->toBeGreaterThan(0);

    expect(GalleryItem::query()->where('legacy_key', 'interior-counter')->exists())->toBeTrue()
        ->and(GalleryItem::query()->where('category', GalleryCategory::Interior)->count())->toBeGreaterThanOrEqual(3);
});
