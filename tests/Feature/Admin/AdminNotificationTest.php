<?php

use App\Enums\AdminNotificationType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Models\AdminNotification;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guest cannot access admin notifications', function () {
    $this->getJson('/api/v1/admin/notifications')->assertUnauthorized();
});

test('customer cannot access admin notifications', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/admin/notifications')
        ->assertForbidden();
});

test('checkout creates a new order notification for each superadmin', function () {
    $adminA = User::factory()->superAdmin()->create();
    $adminB = User::factory()->superAdmin()->create();
    $customer = User::factory()->create(['name' => 'Ayesha']);
    $branch = Branch::factory()->create(['is_active' => true, 'name' => 'Pebbles DHA']);
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->for($category)->create([
        'base_price' => '1000.00',
        'is_active' => true,
        'is_available' => true,
    ]);

    $this->actingAs($customer)->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($customer)->postJson('/api/v1/checkout', [
        'branch_id' => $branch->id,
        'type' => OrderType::Takeaway->value,
        'payment_method' => PaymentMethod::Cod->value,
    ])->assertSuccessful();

    $order = Order::query()->where('user_id', $customer->id)->firstOrFail();

    expect(AdminNotification::query()->count())->toBe(2);

    foreach ([$adminA, $adminB] as $admin) {
        $notification = AdminNotification::query()
            ->where('recipient_user_id', $admin->id)
            ->where('type', AdminNotificationType::OrderCreated)
            ->first();

        expect($notification)->not->toBeNull()
            ->and($notification->title)->toContain($order->public_order_number)
            ->and($notification->branch_id)->toBe($branch->id)
            ->and($notification->action_url)->toBe('/admin/orders/'.$order->public_order_number)
            ->and($notification->isUnread())->toBeTrue()
            ->and($notification->isArchived())->toBeFalse();
    }
});

test('contact message creates admin notification', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->postJson('/api/v1/catalog/contact-messages', [
        'name' => 'Sarah',
        'email' => 'sarah@example.com',
        'phone' => '03001234567',
        'subject' => 'Hello',
        'message' => 'I would like to ask about catering options for a party.',
    ])->assertCreated();

    $notification = AdminNotification::query()
        ->where('recipient_user_id', $admin->id)
        ->where('type', AdminNotificationType::ContactMessageSubmitted)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('New Contact Message')
        ->and($notification->action_url)->toBe('/admin/contact-messages');
});

test('review submission creates admin notification', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->postJson('/api/v1/catalog/reviews', [
        'name' => 'Omar',
        'email' => 'omar@example.com',
        'rating' => 5,
        'review' => 'Great ice cream and very friendly staff overall.',
    ])->assertCreated();

    expect(AdminNotification::query()
        ->where('recipient_user_id', $admin->id)
        ->where('type', AdminNotificationType::ReviewSubmitted)
        ->exists())->toBeTrue();
});

test('customer registration creates admin notification', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->postJson('/api/register', [
        'name' => 'New Customer',
        'email' => 'newcustomer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertCreated();

    expect(AdminNotification::query()
        ->where('recipient_user_id', $admin->id)
        ->where('type', AdminNotificationType::CustomerRegistered)
        ->exists())->toBeTrue();
});

test('customer cancel creates cancelled notification', function () {
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $branch = Branch::factory()->create(['is_active' => true]);
    $order = Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Pending,
    ]);
    Payment::factory()->for($order)->create(['status' => PaymentStatus::Pending]);

    $this->actingAs($customer)
        ->postJson('/api/v1/orders/'.$order->public_order_number.'/cancel', [
            'reason' => 'Ordered by mistake',
        ])
        ->assertSuccessful();

    $notification = AdminNotification::query()
        ->where('recipient_user_id', $admin->id)
        ->where('type', AdminNotificationType::OrderCancelledByCustomer)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toContain($order->public_order_number);
});

test('payment proof upload creates important notification', function () {
    Storage::fake('local');

    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create();
    $branch = Branch::factory()->create(['is_active' => true]);
    $order = Order::factory()->for($customer)->create([
        'branch_id' => $branch->id,
        'status' => OrderStatus::Pending,
    ]);
    $payment = Payment::factory()->for($order)->create([
        'method' => PaymentMethod::JazzCash,
        'status' => PaymentStatus::Pending,
        'amount' => '1500.00',
    ]);

    $this->actingAs($customer)
        ->post('/api/v1/orders/'.$order->public_order_number.'/payment/proof', [
            'proof' => UploadedFile::fake()->image('proof.jpg'),
            'transaction_reference' => 'JC-123',
        ], ['Accept' => 'application/json'])
        ->assertSuccessful();

    $notification = AdminNotification::query()
        ->where('recipient_user_id', $admin->id)
        ->where('type', AdminNotificationType::PaymentProofUploaded)
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->priority->value)->toBe('important')
        ->and($notification->action_url)->toBe('/admin/payments/'.$payment->id);
});

test('admin can manage notification read archive and delete without deleting related order', function () {
    $admin = User::factory()->superAdmin()->create();
    $order = Order::factory()->create();

    $notification = AdminNotification::factory()->create([
        'recipient_user_id' => $admin->id,
        'type' => AdminNotificationType::OrderCreated,
        'category' => AdminNotificationType::OrderCreated->category(),
        'related_type' => $order->getMorphClass(),
        'related_id' => $order->id,
        'action_url' => '/admin/orders/'.$order->public_order_number,
    ]);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/notifications/unread-count')
        ->assertSuccessful()
        ->assertJsonPath('data.unread_count', 1);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/notifications/'.$notification->id.'/read')
        ->assertSuccessful()
        ->assertJsonPath('data.is_read', true)
        ->assertJsonPath('meta.unread_count', 0);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/notifications/'.$notification->id.'/unread')
        ->assertSuccessful()
        ->assertJsonPath('data.is_read', false);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/notifications/'.$notification->id.'/archive')
        ->assertSuccessful()
        ->assertJsonPath('data.is_archived', true);

    $this->actingAs($admin)
        ->getJson('/api/v1/admin/notifications/unread-count')
        ->assertSuccessful()
        ->assertJsonPath('data.unread_count', 0);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/notifications/'.$notification->id.'/unarchive')
        ->assertSuccessful()
        ->assertJsonPath('data.is_archived', false);

    $this->actingAs($admin)
        ->deleteJson('/api/v1/admin/notifications/'.$notification->id)
        ->assertSuccessful();

    expect(AdminNotification::query()->find($notification->id))->toBeNull()
        ->and(Order::query()->find($order->id))->not->toBeNull();
});

test('mark all as read clears unread badge count', function () {
    $admin = User::factory()->superAdmin()->create();

    AdminNotification::factory()->count(3)->create([
        'recipient_user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->postJson('/api/v1/admin/notifications/mark-all-read')
        ->assertSuccessful()
        ->assertJsonPath('data.unread_count', 0);

    expect(AdminNotification::query()
        ->where('recipient_user_id', $admin->id)
        ->unread()
        ->count())->toBe(0);
});

test('admins only see their own notifications', function () {
    $adminA = User::factory()->superAdmin()->create();
    $adminB = User::factory()->superAdmin()->create();

    $owned = AdminNotification::factory()->create([
        'recipient_user_id' => $adminA->id,
        'title' => 'Owned notification',
    ]);

    AdminNotification::factory()->create([
        'recipient_user_id' => $adminB->id,
        'title' => 'Other admin notification',
    ]);

    $this->actingAs($adminA)
        ->getJson('/api/v1/admin/notifications')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $owned->id);

    $this->actingAs($adminA)
        ->getJson('/api/v1/admin/notifications/'.AdminNotification::query()->where('recipient_user_id', $adminB->id)->value('id'))
        ->assertForbidden();
});

test('duplicate order placed notifications are prevented by dedupe key', function () {
    $admin = User::factory()->superAdmin()->create();
    $order = Order::factory()->create();

    OrderPlaced::dispatch($order);
    OrderPlaced::dispatch($order);

    expect(AdminNotification::query()
        ->where('recipient_user_id', $admin->id)
        ->where('type', AdminNotificationType::OrderCreated)
        ->count())->toBe(1);
});

test('unread count ignores archived notifications', function () {
    $admin = User::factory()->superAdmin()->create();
    $service = app(AdminNotificationService::class);

    AdminNotification::factory()->create([
        'recipient_user_id' => $admin->id,
        'read_at' => null,
        'archived_at' => null,
    ]);

    AdminNotification::factory()->archived()->create([
        'recipient_user_id' => $admin->id,
        'read_at' => null,
    ]);

    expect($service->unreadCountFor($admin))->toBe(1);
});
