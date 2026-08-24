<?php

use App\Models\User;
use App\Support\BrandingSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('public branding api returns the active logo url', function () {
    Storage::fake('public');
    Storage::disk('public')->put('branding/test-logo.png', 'logo');

    BrandingSettings::storeLogo(UploadedFile::fake()->image('test-logo.png'));

    $this->getJson('/api/v1/catalog/branding')
        ->assertSuccessful()
        ->assertJsonPath('data.logo.path', fn ($path) => str_starts_with((string) $path, 'branding/'))
        ->assertJsonPath('data.logo.url', fn ($url) => is_string($url) && $url !== '');
});

test('superadmin can upload replace and remove the website logo', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $first = UploadedFile::fake()->image('logo-a.png');
    $second = UploadedFile::fake()->image('logo-b.png');

    $this->actingAs($admin)
        ->post('/api/v1/admin/settings/logo', ['logo' => $first])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Logo updated successfully.');

    $firstPath = BrandingSettings::logoPath();

    $this->actingAs($admin)
        ->post('/api/v1/admin/settings/logo', ['logo' => $second])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Logo updated successfully.');

    expect(Storage::disk('public')->exists($firstPath))->toBeFalse()
        ->and(Storage::disk('public')->exists((string) BrandingSettings::logoPath()))->toBeTrue();

    $this->getJson('/api/v1/catalog/branding')
        ->assertSuccessful()
        ->assertJsonPath('data.logo.url', fn ($url) => is_string($url) && $url !== '');

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/settings/logo')
        ->assertSuccessful()
        ->assertJsonPath('message', 'Logo removed successfully.')
        ->assertJsonPath('data.logo', null);

    $this->getJson('/api/v1/catalog/branding')
        ->assertSuccessful()
        ->assertJsonPath('data.logo', null);
});

test('guest cannot manage the website logo', function () {
    Storage::fake('public');

    $this->postJson('/api/v1/admin/settings/logo', [
        'logo' => UploadedFile::fake()->image('logo.png'),
    ])->assertUnauthorized();

    $this->deleteJson('/api/v1/admin/settings/logo')->assertUnauthorized();
});
