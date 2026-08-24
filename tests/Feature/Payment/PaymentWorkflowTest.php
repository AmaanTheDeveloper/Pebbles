<?php

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\User;
use App\Support\PaymentSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function fakeProofUpload(string $name = 'receipt.jpg'): UploadedFile
{
    return UploadedFile::fake()->create($name, 120, 'image/jpeg');
}

function createPayableOrder(User $user, array $paymentOverrides = []): Order
{
    $branch = Branch::factory()->create(['is_active' => true]);
    $order = Order::factory()->for($user)->create([
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway,
        'status' => OrderStatus::Pending,
        'subtotal' => '1000.00',
        'grand_total' => '1000.00',
        'delivery_fee' => '0.00',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_name' => 'Snapshot Burger',
        'unit_price' => '1000.00',
        'quantity' => 1,
        'line_total' => '1000.00',
    ]);

    Payment::factory()->for($order)->create(array_merge([
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
        'amount' => '1000.00',
    ], $paymentOverrides));

    return $order->fresh('payment');
}

test('payment methods endpoint returns enabled customer-facing methods', function () {
    PaymentSettings::ensureDefaults();

    $response = $this->getJson('/api/v1/payment-methods');

    $response->assertSuccessful()
        ->assertJsonFragment(['id' => 'cod', 'requires_proof' => false])
        ->assertJsonFragment(['id' => 'bank_transfer', 'requires_proof' => true]);

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->not->toContain('easypaisa');

    $payload = json_encode($response->json());
    expect($payload)->not->toContain('secret')
        ->and($payload)->not->toContain('password');
});

test('customer can select a payment method on their own order', function () {
    $user = User::factory()->create();
    $order = createPayableOrder($user);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/payment', [
            'method' => PaymentMethod::BankTransfer->value,
            'transaction_reference' => 'TX-123',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.payment.method', PaymentMethod::BankTransfer->value)
        ->assertJsonPath('data.payment.status', PaymentStatus::Pending->value)
        ->assertJsonPath('data.payment.requires_proof', true)
        ->assertJsonPath('data.payment.transaction_reference', 'TX-123');
});

test('customer cannot change another users payment method', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $order = createPayableOrder($owner);

    $this->actingAs($intruder)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/payment', [
            'method' => PaymentMethod::JazzCash->value,
        ])
        ->assertForbidden();
});

test('customer can upload payment proof for methods that require it', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $order = createPayableOrder($user, [
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Pending,
    ]);

    $file = fakeProofUpload('receipt.jpg');

    $this->actingAs($user)
        ->post('/api/v1/orders/'.$order->public_order_number.'/payment/proof', [
            'proof' => $file,
            'transaction_reference' => 'BANK-9',
        ], ['Accept' => 'application/json'])
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Submitted->value)
        ->assertJsonPath('data.payment.proof.original_filename', 'receipt.jpg');

    $proof = PaymentProof::query()->first();
    expect($proof)->not->toBeNull()
        ->and($proof->isStoredPrivately())->toBeTrue()
        ->and($proof->path)->toStartWith('payment-proofs/')
        ->and(Storage::disk('local')->exists($proof->path))->toBeTrue();
});

test('cod does not accept payment proof uploads', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $order = createPayableOrder($user, ['method' => PaymentMethod::Cod]);

    $this->actingAs($user)
        ->post('/api/v1/orders/'.$order->public_order_number.'/payment/proof', [
            'proof' => fakeProofUpload('receipt.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable();
});

test('invalid proof file types are rejected', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $order = createPayableOrder($user, [
        'method' => PaymentMethod::EasyPaisa,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($user)
        ->post('/api/v1/orders/'.$order->public_order_number.'/payment/proof', [
            'proof' => UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['proof']);
});

test('customer can download own proof and cannot download another customers proof', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $order = createPayableOrder($owner, [
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
    ]);

    Storage::disk('local')->put('payment-proofs/demo.jpg', 'fake-bytes');
    $proof = PaymentProof::factory()->for($order->payment)->create([
        'disk' => 'local',
        'path' => 'payment-proofs/demo.jpg',
        'original_filename' => 'demo.jpg',
        'mime_type' => 'image/jpeg',
    ]);

    $this->actingAs($owner)
        ->get('/api/v1/orders/'.$order->public_order_number.'/payment/proof/'.$proof->id)
        ->assertSuccessful();

    $this->actingAs($intruder)
        ->get('/api/v1/orders/'.$order->public_order_number.'/payment/proof/'.$proof->id)
        ->assertForbidden();

    expect(Storage::disk('public')->exists($proof->path))->toBeFalse()
        ->and($proof->isStoredPrivately())->toBeTrue();
});

test('superadmin can verify submitted payment without confirming pending order', function () {
    Storage::fake('local');
    $customer = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $order = createPayableOrder($customer, [
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
    ]);
    PaymentProof::factory()->for($order->payment)->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/internal/orders/'.$order->public_order_number.'/payment/verify')
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Verified->value)
        ->assertJsonPath('data.status', OrderStatus::Pending->value);

    expect($order->fresh()->payment->verified_by)->toBe($admin->id)
        ->and($order->fresh()->payment->paid_at)->not->toBeNull()
        ->and($order->fresh()->confirmed_at)->toBeNull();
});

test('superadmin can reject submitted payment and customer may resubmit', function () {
    Storage::fake('local');
    $customer = User::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $order = createPayableOrder($customer, [
        'method' => PaymentMethod::JazzCash,
        'status' => PaymentStatus::Submitted,
    ]);
    PaymentProof::factory()->for($order->payment)->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/internal/orders/'.$order->public_order_number.'/payment/reject', [
            'reason' => 'Unreadable receipt',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Rejected->value);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);

    $this->actingAs($customer)
        ->post('/api/v1/orders/'.$order->public_order_number.'/payment/proof', [
            'proof' => fakeProofUpload('better.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Submitted->value);

    expect(PaymentProof::query()->count())->toBe(2);
});

test('customer cannot verify or reject payments', function () {
    $customer = User::factory()->create();
    $order = createPayableOrder($customer, [
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
    ]);

    $this->actingAs($customer)
        ->postJson('/api/v1/internal/orders/'.$order->public_order_number.'/payment/verify')
        ->assertForbidden();

    $this->actingAs($customer)
        ->postJson('/api/v1/internal/orders/'.$order->public_order_number.'/payment/reject')
        ->assertForbidden();
});

test('verified payment cannot be replaced by customer proof upload', function () {
    Storage::fake('local');
    $customer = User::factory()->create();
    $order = createPayableOrder($customer, [
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Verified,
    ]);
    PaymentProof::factory()->for($order->payment)->create();

    $this->actingAs($customer)
        ->post('/api/v1/orders/'.$order->public_order_number.'/payment/proof', [
            'proof' => fakeProofUpload('again.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable();
});

test('verified payment cannot be verified or rejected again', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = createPayableOrder($customer, [
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Verified,
    ]);

    $this->actingAs($admin)
        ->postJson('/api/v1/internal/orders/'.$order->public_order_number.'/payment/verify')
        ->assertUnprocessable();

    $this->actingAs($admin)
        ->postJson('/api/v1/internal/orders/'.$order->public_order_number.'/payment/reject')
        ->assertUnprocessable();
});

test('cod pending payment can be verified by staff without confirming the order', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $order = createPayableOrder($customer, [
        'method' => PaymentMethod::Cod,
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->postJson('/api/v1/internal/orders/'.$order->public_order_number.'/payment/verify')
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Verified->value)
        ->assertJsonPath('data.status', OrderStatus::Pending->value);
});
