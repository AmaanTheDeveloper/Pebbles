<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\User;
use App\Support\PublicMediaUrl;
use Illuminate\Support\Facades\Storage;

test('payment proofs are stored on the private local disk path concept', function () {
    $proof = PaymentProof::factory()->create([
        'disk' => 'local',
        'path' => 'payment-proofs/demo-receipt.jpg',
    ]);

    expect($proof->isStoredPrivately())->toBeTrue()
        ->and($proof->disk)->toBe('local')
        ->and($proof->path)->toStartWith('payment-proofs/');
});

test('payment proofs are not considered public storage', function () {
    $proof = PaymentProof::factory()->create([
        'disk' => 'public',
        'path' => 'payment-proofs/public-leak.jpg',
    ]);

    expect($proof->isStoredPrivately())->toBeFalse();
});

test('configured payment proof disk roots under private storage', function () {
    $disk = config('pebbles.payment_proof_disk', 'local');

    expect($disk)->toBe('local')
        ->and(config('filesystems.disks.local.root'))->toBe(storage_path('app/private'))
        ->and(config('filesystems.disks.public.root'))->toBe(storage_path('app/public'));

    Storage::fake('local');
    Storage::disk('local')->put('payment-proofs/test.jpg', 'fake-image');

    expect(Storage::disk('local')->exists('payment-proofs/test.jpg'))->toBeTrue();
});

test('payment belongs to the correct order', function () {
    $payment = Payment::factory()->create(['amount' => '500.00']);

    expect($payment->order->id)->toBe($payment->order_id)
        ->and($payment->order->payment->is($payment))->toBeTrue();
});

test('manager cannot download payment proof for another branch', function () {
    Storage::fake('local');

    $branchA = Branch::factory()->create(['is_active' => true]);
    $branchB = Branch::factory()->create(['is_active' => true]);
    $manager = User::factory()->manager($branchA)->create();
    $customer = User::factory()->create();

    $order = Order::factory()->for($customer)->create([
        'branch_id' => $branchB->id,
        'status' => OrderStatus::Pending,
        'subtotal' => '1000.00',
        'grand_total' => '1000.00',
        'delivery_fee' => '0.00',
    ]);
    OrderItem::factory()->for($order)->create([
        'product_name' => 'Other Branch Scoop',
        'unit_price' => '1000.00',
        'quantity' => 1,
        'line_total' => '1000.00',
    ]);
    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
        'amount' => '1000.00',
    ]);

    Storage::disk('local')->put('payment-proofs/other-branch.jpg', 'secret-bytes');
    $proof = PaymentProof::factory()->for($order->fresh()->payment)->create([
        'disk' => 'local',
        'path' => 'payment-proofs/other-branch.jpg',
        'original_filename' => 'other-branch.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    $this->actingAs($manager)
        ->get('/api/v1/admin/orders/'.$order->public_order_number.'/payment/proof/'.$proof->id)
        ->assertForbidden();
});

test('customer cannot download another customers payment proof', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $branch = Branch::factory()->create(['is_active' => true]);

    $order = Order::factory()->for($owner)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Pending,
        'subtotal' => '500.00',
        'grand_total' => '500.00',
        'delivery_fee' => '0.00',
    ]);
    OrderItem::factory()->for($order)->create([
        'product_name' => 'Owner Scoop',
        'unit_price' => '500.00',
        'quantity' => 1,
        'line_total' => '500.00',
    ]);
    Payment::factory()->for($order)->create([
        'method' => PaymentMethod::EasyPaisa,
        'status' => PaymentStatus::Submitted,
        'amount' => '500.00',
    ]);

    Storage::disk('local')->put('payment-proofs/owner-only.jpg', 'owner-bytes');
    $proof = PaymentProof::factory()->for($order->fresh()->payment)->create([
        'disk' => 'local',
        'path' => 'payment-proofs/owner-only.jpg',
        'original_filename' => 'owner-only.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    $this->actingAs($intruder)
        ->get('/api/v1/orders/'.$order->public_order_number.'/payment/proof/'.$proof->id)
        ->assertForbidden();

    $this->actingAs($owner)
        ->get('/api/v1/orders/'.$order->public_order_number.'/payment/proof/'.$proof->id)
        ->assertSuccessful();
});

test('public media url uses storage url and optional media url override', function () {
    $default = PublicMediaUrl::fromPath('products/demo.jpg');
    expect($default)->toContain('/storage/products/demo.jpg');

    config(['pebbles.media_url' => 'https://cdn.example.com/media']);
    expect(PublicMediaUrl::fromPath('products/demo.jpg'))->toBe('https://cdn.example.com/media/products/demo.jpg');

    expect(PublicMediaUrl::fromPath(null))->toBeNull()
        ->and(PublicMediaUrl::fromPath(''))->toBeNull();
});
