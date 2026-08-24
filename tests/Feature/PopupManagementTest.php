<?php

use App\Models\User;
use App\Support\PopupSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('public popup api returns null when popup is inactive', function () {
    PopupSettings::update(['is_active' => false]);

    $this->getJson('/api/v1/catalog/popup')
        ->assertSuccessful()
        ->assertJsonPath('data', null);
});

test('public popup api returns active popup configuration', function () {
    PopupSettings::update([
        'is_active' => true,
        'heading' => 'Welcome to Pebbles',
        'message' => 'Something Delicious Is Waiting For You',
        'description' => 'Enjoy our latest special offer.',
        'button_text' => 'Order Online',
        'button_url' => '/order',
        'delay_ms' => 700,
        'frequency' => 'session',
    ]);

    $this->getJson('/api/v1/catalog/popup')
        ->assertSuccessful()
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.heading', 'Welcome to Pebbles')
        ->assertJsonPath('data.message', 'Something Delicious Is Waiting For You')
        ->assertJsonPath('data.button_url', '/order')
        ->assertJsonPath('data.delay_ms', 700);
});

test('superadmin can manage popup settings and image', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings/popup', [
            'is_active' => true,
            'heading' => 'Welcome Back',
            'message' => 'Fresh treats are waiting',
            'delay_ms' => 1000,
            'frequency' => 'day',
        ])
        ->assertSuccessful()
        ->assertJsonPath('message', 'Popup settings updated successfully.')
        ->assertJsonPath('data.popup.heading', 'Welcome Back');

    $this->actingAs($admin)
        ->post('/api/v1/admin/settings/popup/image', [
            'image' => UploadedFile::fake()->image('popup.jpg'),
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.popup.image.url', fn ($url) => is_string($url) && $url !== '');

    $this->getJson('/api/v1/catalog/popup')
        ->assertSuccessful()
        ->assertJsonPath('data.heading', 'Welcome Back')
        ->assertJsonPath('data.image.url', fn ($url) => is_string($url) && $url !== '');

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/settings/popup/image')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings/popup', ['is_active' => false])
        ->assertSuccessful();

    $this->getJson('/api/v1/catalog/popup')
        ->assertSuccessful()
        ->assertJsonPath('data', null);
});
