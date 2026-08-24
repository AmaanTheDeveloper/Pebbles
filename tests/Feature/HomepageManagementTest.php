<?php

use App\Models\HomepageFeature;
use App\Models\HomepageSection;
use App\Models\User;
use App\Support\HomepageContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('public homepage api returns active sections and content blocks', function () {
    HomepageContent::seedIfEmpty();

    HomepageSection::query()->where('key', 'final_cta')->update(['is_active' => false]);

    $this->getJson('/api/v1/catalog/homepage')
        ->assertSuccessful()
        ->assertJsonPath('data.sections.0.key', 'hero')
        ->assertJsonMissingPath('data.sections.11.key');
});

test('superadmin can manage homepage hero section and features', function () {
    Storage::fake('public');
    HomepageContent::seedIfEmpty();

    $admin = User::factory()->superAdmin()->create();
    $hero = HomepageSection::query()->where('key', 'hero')->firstOrFail();

    $this->actingAs($admin)
        ->patch("/api/v1/admin/homepage/sections/{$hero->key}", [
            'settings' => [
                'heading_line1' => 'Custom heading',
                'heading_accent' => 'Custom accent',
            ],
            'hero_image' => UploadedFile::fake()->image('hero.jpg'),
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.settings.heading_line1', 'Custom heading')
        ->assertJsonPath('data.settings.hero_image.url', fn ($url) => is_string($url) && $url !== '');

    $feature = HomepageFeature::query()->firstOrFail();

    $this->actingAs($admin)
        ->patchJson("/api/v1/admin/homepage/features/{$feature->id}", [
            'title' => 'Updated feature',
            'is_active' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'Updated feature')
        ->assertJsonPath('data.is_active', false);

    $this->getJson('/api/v1/catalog/homepage')
        ->assertSuccessful()
        ->assertJsonPath('data.sections.0.settings.heading_line1', 'Custom heading');
});

test('superadmin can reorder homepage sections', function () {
    HomepageContent::seedIfEmpty();
    $admin = User::factory()->superAdmin()->create();

    $sections = HomepageSection::query()->orderBy('display_order')->get();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/homepage/sections/reorder', [
            'sections' => $sections->map(fn (HomepageSection $section, int $index): array => [
                'key' => $section->key,
                'display_order' => 100 - $index,
            ])->all(),
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.summary.active_sections', fn ($count) => $count >= 1);
});
