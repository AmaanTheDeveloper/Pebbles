<?php

use App\Models\Setting;
use App\Models\User;
use App\Support\PaymentSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('payment methods endpoint excludes disabled easypaisa by default', function () {
    PaymentSettings::ensureDefaults();

    $response = $this->getJson('/api/v1/payment-methods');

    $response->assertSuccessful()
        ->assertJsonFragment(['id' => 'cod'])
        ->assertJsonFragment(['id' => 'jazzcash'])
        ->assertJsonFragment(['id' => 'bank_transfer']);

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->not->toContain('easypaisa');
});

test('payment methods endpoint returns admin-configured jazzcash business details', function () {
    PaymentSettings::update([
        'jazzcash' => [
            'business_name' => 'PEBBLES',
            'till_id' => '984159150',
            'instructions' => 'Send exact amount and upload screenshot.',
        ],
    ]);

    $response = $this->getJson('/api/v1/payment-methods');

    $response->assertSuccessful();

    $jazzcash = collect($response->json('data'))->firstWhere('id', 'jazzcash');

    expect($jazzcash)->not->toBeNull()
        ->and($jazzcash['details']['business_name'])->toBe('PEBBLES')
        ->and($jazzcash['details']['till_id'])->toBe('984159150')
        ->and($jazzcash['details'])->not->toHaveKey('number')
        ->and($jazzcash['instructions'])->toBe('Send exact amount and upload screenshot.');
});

test('legacy jazzcash account name maps to business name', function () {
    Setting::setValue(PaymentSettings::SETTING_KEY, [
        'jazzcash' => [
            'account_name' => 'Legacy Pebbles Name',
            'number' => '03001234567',
        ],
    ], 'json');

    $details = PaymentSettings::customerDetails('jazzcash');

    expect($details['business_name'])->toBe('Legacy Pebbles Name')
        ->and($details['till_id'])->toBe('984159150');
});

test('superadmin can update payment settings', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->patchJson('/api/v1/admin/settings/payment', [
            'methods' => [
                'jazzcash' => ['enabled' => true],
                'bank_transfer' => ['enabled' => false],
            ],
            'bank_transfer' => [
                'bank_name' => 'Meezan Bank',
                'account_number' => '1234567890',
            ],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.payment.bank_transfer.bank_name', 'Meezan Bank')
        ->assertJsonPath('data.payment.methods.bank_transfer.enabled', false);

    expect(PaymentSettings::isMethodEnabled('bank_transfer'))->toBeFalse()
        ->and(PaymentSettings::config()['bank_transfer']['account_number'])->toBe('1234567890');
});

test('superadmin can upload and remove jazzcash qr image', function () {
    Storage::fake('public');
    $admin = User::factory()->superAdmin()->create();

    $file = UploadedFile::fake()->image('jazzcash-qr.jpg', 800, 1200);

    $this->actingAs($admin)
        ->post('/api/v1/admin/settings/payment/jazzcash/qr', [
            'image' => $file,
        ], ['Accept' => 'application/json'])
        ->assertSuccessful()
        ->assertJsonPath('data.payment.jazzcash.qr_image.url', fn ($url) => is_string($url) && $url !== '');

    $path = PaymentSettings::jazzcashQrImagePath();
    expect($path)->not->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeTrue();

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/settings/payment/jazzcash/qr')
        ->assertSuccessful();

    expect(PaymentSettings::jazzcashQrImagePath())->toBeNull();
});

test('disabled payment method is hidden from customers', function () {
    PaymentSettings::update([
        'methods' => [
            'jazzcash' => ['enabled' => false],
        ],
    ]);

    $response = $this->getJson('/api/v1/payment-methods');

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->not->toContain('jazzcash');
});

test('admin settings show includes payment configuration', function () {
    $admin = User::factory()->superAdmin()->create();
    PaymentSettings::ensureDefaults();

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/settings')
        ->assertSuccessful()
        ->assertJsonPath('data.payment.methods.cod.enabled', true)
        ->assertJsonPath('data.payment.jazzcash.business_name', 'PEBBLES')
        ->assertJsonPath('data.payment.jazzcash.till_id', '984159150')
        ->assertJsonStructure([
            'data' => [
                'payment' => [
                    'methods',
                    'jazzcash' => ['business_name', 'till_id', 'qr_image', 'instructions'],
                    'bank_transfer',
                ],
            ],
        ]);
});

test('payment settings are persisted in settings table', function () {
    PaymentSettings::update([
        'jazzcash' => [
            'till_id' => '984159150',
        ],
    ]);

    $stored = Setting::getValue(PaymentSettings::SETTING_KEY);

    expect($stored['jazzcash']['till_id'])->toBe('984159150');
});

test('jazzcash defaults include business name and till id', function () {
    PaymentSettings::ensureDefaults();

    $defaults = PaymentSettings::config()['jazzcash'];

    expect($defaults['business_name'])->toBe('PEBBLES')
        ->and($defaults['till_id'])->toBe('984159150')
        ->and($defaults['qr_image_path'])->toBeNull();
});
