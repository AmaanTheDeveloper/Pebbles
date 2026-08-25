<?php

use App\Models\User;
use App\Support\BrandingSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('public branding api returns the active favicon url', function () {
    Storage::fake('public');

    BrandingSettings::storeFavicon(UploadedFile::fake()->image('favicon.png', 32, 32));

    $this->getJson('/api/v1/catalog/branding')
        ->assertSuccessful()
        ->assertJsonPath('data.favicon.path', fn ($path) => str_starts_with((string) $path, 'branding/'))
        ->assertJsonPath('data.favicon.url', fn ($url) => is_string($url) && $url !== '');
});

test('superadmin can upload replace and remove the website favicon', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $first = UploadedFile::fake()->image('favicon-a.png', 32, 32);
    $second = UploadedFile::fake()->image('favicon-b.webp', 32, 32);

    $this->actingAs($admin)
        ->post('/api/v1/admin/settings/favicon', ['favicon' => $first])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Favicon updated successfully.');

    $firstPath = BrandingSettings::faviconPath();

    $this->actingAs($admin)
        ->post('/api/v1/admin/settings/favicon', ['favicon' => $second])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Favicon updated successfully.');

    expect(Storage::disk('public')->exists($firstPath))->toBeFalse()
        ->and(Storage::disk('public')->exists((string) BrandingSettings::faviconPath()))->toBeTrue();

    $this->getJson('/api/v1/catalog/branding')
        ->assertSuccessful()
        ->assertJsonPath('data.favicon.url', fn ($url) => is_string($url) && $url !== '');

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/settings/favicon')
        ->assertSuccessful()
        ->assertJsonPath('message', 'Favicon removed successfully.')
        ->assertJsonPath('data.favicon', null);

    $this->getJson('/api/v1/catalog/branding')
        ->assertSuccessful()
        ->assertJsonPath('data.favicon', null);
});

test('guest cannot manage the website favicon', function () {
    Storage::fake('public');

    $this->postJson('/api/v1/admin/settings/favicon', [
        'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
    ])->assertUnauthorized();

    $this->deleteJson('/api/v1/admin/settings/favicon')->assertUnauthorized();
});
