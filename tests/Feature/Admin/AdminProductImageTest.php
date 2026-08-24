<?php

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function fixtureImage(string $name): UploadedFile
{
    $path = base_path('tests/Fixtures/images/'.$name);

    return new UploadedFile($path, $name, 'image/jpeg', null, true);
}

test('guest and customer cannot upload product images', function () {
    Storage::fake('public');
    $product = Product::factory()->create();

    $this->post('/api/v1/admin/products/'.$product->id.'/images', [
        'image' => fixtureImage('square-600.jpg'),
    ], ['Accept' => 'application/json'])->assertUnauthorized();

    $customer = User::factory()->create(['role' => UserRole::User]);

    $this->actingAs($customer)
        ->post('/api/v1/admin/products/'.$product->id.'/images', [
            'image' => fixtureImage('square-600.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertForbidden();
});

test('superadmin can upload rectangular image and stored file is square', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $product = Product::factory()->create(['name' => 'Chocolate Brownie Shake']);

    $response = $this->actingAs($admin)
        ->post('/api/v1/admin/products/'.$product->id.'/images', [
            'image' => fixtureImage('rect-800x1200.jpg'),
            'alt_text' => 'Brownie shake',
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.is_primary', true)
        ->assertJsonPath('data.alt_text', 'Brownie shake');

    $path = $response->json('data.path');
    expect($path)->toStartWith('products/'.$product->id.'/')
        ->and(Storage::disk('public')->exists($path))->toBeTrue();

    $absolute = Storage::disk('public')->path($path);
    [$width, $height] = getimagesize($absolute);
    expect($width)->toBe($height)
        ->and($width)->toBeLessThanOrEqual(2048)
        ->and($width)->toBeGreaterThanOrEqual(600);

    $payload = json_encode($response->json());
    expect($payload)->not->toContain(storage_path())
        ->and($payload)->not->toContain('C:\\');
});

test('already square uploads remain square', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $product = Product::factory()->create();

    $path = $this->actingAs($admin)
        ->post('/api/v1/admin/products/'.$product->id.'/images', [
            'image' => fixtureImage('square-600.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->json('data.path');

    [$width, $height] = getimagesize(Storage::disk('public')->path($path));
    expect($width)->toBe($height)->and($width)->toBe(600);
});

test('tiny images and invalid files are rejected', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $product = Product::factory()->create();

    $this->actingAs($admin)
        ->post('/api/v1/admin/products/'.$product->id.'/images', [
            'image' => fixtureImage('tiny-100.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);

    $this->actingAs($admin)
        ->post('/api/v1/admin/products/'.$product->id.'/images', [
            'image' => UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

test('primary image switching and delete promotion work', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $product = Product::factory()->create();

    $first = $this->actingAs($admin)
        ->post('/api/v1/admin/products/'.$product->id.'/images', [
            'image' => fixtureImage('square-600.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->json('data');

    $second = $this->actingAs($admin)
        ->post('/api/v1/admin/products/'.$product->id.'/images', [
            'image' => fixtureImage('rect-800x1200.jpg'),
            'is_primary' => false,
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->json('data');

    expect($first['is_primary'])->toBeTrue()
        ->and($second['is_primary'])->toBeFalse();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/products/'.$product->id.'/images/'.$second['id'].'/primary')
        ->assertSuccessful()
        ->assertJsonPath('data.is_primary', true);

    expect(ProductImage::query()->where('product_id', $product->id)->where('is_primary', true)->count())->toBe(1);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$product->id.'/images/'.$second['id'])
        ->assertNoContent();

    $remaining = ProductImage::query()->where('product_id', $product->id)->first();
    expect($remaining)->not->toBeNull()
        ->and($remaining->is_primary)->toBeTrue()
        ->and($remaining->id)->toBe($first['id']);
});

test('image idor is rejected across products', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();

    $image = ProductImage::factory()->for($productA)->primary()->create([
        'path' => 'products/'.$productA->id.'/existing.jpg',
    ]);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/products/'.$productB->id.'/images/'.$image->id)
        ->assertNotFound();

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/products/'.$productB->id.'/images/'.$image->id.'/primary')
        ->assertNotFound();
});

test('catalog product detail still returns primary image and products without images work', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();
    $withImage = Product::factory()->create(['is_active' => true, 'is_available' => true, 'slug' => 'brownie-shake']);
    $withoutImage = Product::factory()->create(['is_active' => true, 'is_available' => true, 'slug' => 'plain-tea']);

    $this->actingAs($admin)
        ->post('/api/v1/admin/products/'.$withImage->id.'/images', [
            'image' => fixtureImage('rect-800x1200.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    $this->getJson('/api/v1/catalog/products/brownie-shake')
        ->assertSuccessful()
        ->assertJsonPath('data.images.0.is_primary', true)
        ->assertJsonStructure(['data' => ['images' => [['url', 'alt_text']]]]);

    $this->getJson('/api/v1/catalog/products/plain-tea')
        ->assertSuccessful()
        ->assertJsonPath('data.images', []);
});
