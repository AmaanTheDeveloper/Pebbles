<?php

use App\Enums\OfferType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('superadmin can create update deactivate and delete offers', function () {
    $admin = User::factory()->superAdmin()->create();

    $created = $this->actingAs($admin)
        ->postJson('/api/v1/admin/offers', [
            'title' => 'Family Combo',
            'type' => OfferType::ComboOffers->value,
            'short_description' => 'Shareable meal deal',
            'description' => 'Two mains and drinks.',
            'offer_value' => 'Save Rs 300',
            'cta_label' => 'Order Now',
            'cta_url' => '/order',
            'is_active' => true,
            'sort_order' => 1,
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Family Combo')
        ->assertJsonPath('data.type', OfferType::ComboOffers->value)
        ->assertJsonPath('data.type_label', 'Combo Offers')
        ->json('data');

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/offers/'.$created['id'], [
            'title' => 'Weekend Family Combo',
            'is_active' => false,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'Weekend Family Combo')
        ->assertJsonPath('data.is_active', false);

    $this->getJson('/api/v1/catalog/offers')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/offers/'.$created['id'], [
            'is_active' => true,
        ])
        ->assertSuccessful();

    $this->getJson('/api/v1/catalog/offers')
        ->assertSuccessful()
        ->assertJsonPath('data.0.title', 'Weekend Family Combo');

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/offers/'.$created['id'])
        ->assertNoContent();

    $this->getJson('/api/v1/catalog/offers')
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

test('non-admin cannot manage offers', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/admin/offers', [
            'title' => 'Blocked',
            'type' => OfferType::Discounts->value,
        ])
        ->assertForbidden();
});

test('offer validation requires title and type', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/offers', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'type']);
});

test('superadmin can upload and replace offer images', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $created = $this->actingAs($admin)
        ->post('/api/v1/admin/offers', [
            'title' => 'Upload Combo',
            'type' => OfferType::ComboOffers->value,
            'is_active' => true,
            'image' => UploadedFile::fake()->image('combo.jpg', 800, 500),
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Upload Combo')
        ->json('data');

    expect($created['image_path'])->toStartWith('offers/')
        ->and($created['image']['url'])->not->toBeEmpty();

    Storage::disk('public')->assertExists($created['image_path']);

    $updated = $this->actingAs($admin)
        ->post('/api/v1/admin/offers/'.$created['id'], [
            '_method' => 'PATCH',
            'title' => 'Upload Combo',
            'type' => OfferType::ComboOffers->value,
            'is_active' => true,
            'image' => UploadedFile::fake()->image('combo-new.png', 640, 400),
        ])
        ->assertSuccessful()
        ->json('data');

    expect($updated['image_path'])->not->toBe($created['image_path']);
    Storage::disk('public')->assertMissing($created['image_path']);
    Storage::disk('public')->assertExists($updated['image_path']);

    $this->actingAs($admin)
        ->post('/api/v1/admin/offers/'.$created['id'], [
            '_method' => 'PATCH',
            'title' => 'Upload Combo Kept',
            'type' => OfferType::ComboOffers->value,
            'is_active' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.image_path', $updated['image_path']);
});

test('invalid offer image uploads are rejected', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post('/api/v1/admin/offers', [
            'title' => 'Bad Image',
            'type' => OfferType::Discounts->value,
            'image' => UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});
