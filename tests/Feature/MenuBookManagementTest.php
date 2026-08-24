<?php

use App\Models\MenuBookPage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('public menu book api returns only active pages ordered by display order', function () {
    MenuBookPage::factory()->create([
        'title' => 'Hidden Page',
        'display_order' => 1,
        'is_active' => false,
    ]);

    MenuBookPage::factory()->create([
        'title' => 'Main Menu',
        'display_order' => 2,
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/catalog/menu-book')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Main Menu');
});

test('superadmin can manage menu book pages', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $file = UploadedFile::fake()->image('menu-page.jpg', 1200, 1800);

    $create = $this->actingAs($admin)
        ->post('/api/v1/admin/menu-book', [
            'title' => 'Shakes & Ice Cream',
            'display_order' => 2,
            'is_active' => true,
            'image' => $file,
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Shakes & Ice Cream');

    $pageId = $create->json('data.id');

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/menu-book/{$pageId}/status", ['is_active' => false])
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', false);

    $this->getJson('/api/v1/catalog/menu-book')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/menu-book/{$pageId}/status", ['is_active' => true])
        ->assertSuccessful();

    $this->getJson('/api/v1/catalog/menu-book')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Shakes & Ice Cream');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/admin/menu-book/{$pageId}")
        ->assertSuccessful();
});
