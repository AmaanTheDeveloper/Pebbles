<?php

/**
 * API-level critical end-to-end flows.
 *
 * True browser E2E is deferred until pest-plugin-browser (or another approved
 * browser runner) is installed. These Feature tests exercise the same customer
 * and manager journeys over HTTP/JSON without Playwright or browser deps.
 */

use App\Enums\OfferType;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Offer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Product;
use App\Models\User;
use App\Support\GuestCartCookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withHeader('Origin', 'http://localhost');
    $this->withCredentials();
});

function e2eProduct(array $attributes = [], ?Category $category = null): Product
{
    $category ??= Category::factory()->create(['is_active' => true]);

    return Product::factory()->for($category)->create(array_merge([
        'base_price' => '1000.00',
        'is_active' => true,
        'is_available' => true,
    ], $attributes));
}

function e2eBranch(): Branch
{
    return Branch::factory()->create(['is_active' => true]);
}

test('register verify add to cart checkout takeaway and pay COD', function () {
    $branch = e2eBranch();
    $product = e2eProduct();

    $this->postJson('/api/register', [
        'name' => 'E2E Customer',
        'email' => 'e2e-register@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertCreated();

    $user = User::query()->where('email', 'e2e-register@example.com')->firstOrFail();

    expect($user->hasVerifiedEmail())->toBeFalse();

    $user->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($user->fresh())
        ->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
        ->assertSuccessful();

    $checkout = $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
            'payment_method' => PaymentMethod::Cod->value,
        ])
        ->assertSuccessful();

    $orderNumber = $checkout->json('data.order_number');
    $order = Order::query()->where('public_order_number', $orderNumber)->firstOrFail();

    expect($order->type)->toBe(OrderType::Takeaway)
        ->and($order->payment->method)->toBe(PaymentMethod::Cod)
        ->and($order->payment->status)->toBe(PaymentStatus::Pending)
        ->and((float) $order->grand_total)->toBe(1000.0);

    $this->actingAs($user)
        ->postJson('/api/v1/orders/'.$orderNumber.'/payment', [
            'method' => PaymentMethod::Cod->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.payment.method', PaymentMethod::Cod->value);
});

test('guest cart merges on login then checkout completes', function () {
    $branch = e2eBranch();
    $product = e2eProduct(['base_price' => '500.00']);
    $user = User::factory()->create([
        'email' => 'e2e-merge@example.com',
        'password' => Hash::make('password'),
    ]);

    $guestToken = (string) Str::uuid();
    $guestCart = Cart::factory()->guest($guestToken)->create([
        'branch_id' => $branch->id,
    ]);
    CartItem::factory()->create([
        'cart_id' => $guestCart->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $this->withUnencryptedCookie(GuestCartCookie::NAME, $guestToken)
        ->postJson('/api/login', [
            'email' => 'e2e-merge@example.com',
            'password' => 'password',
        ])
        ->assertSuccessful()
        ->assertCookieExpired(GuestCartCookie::NAME);

    $userCart = Cart::query()->where('user_id', $user->id)->first();

    expect($userCart)->not->toBeNull()
        ->and($userCart->items()->sum('quantity'))->toBe(2);

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
            'payment_method' => PaymentMethod::Cod->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.total', '1000.00');
});

test('targeted offer plus coupon checkout stacks discounts correctly', function () {
    $branch = e2eBranch();
    $burgers = Category::factory()->create(['is_active' => true]);
    $drinks = Category::factory()->create(['is_active' => true]);
    $burger = e2eProduct(['base_price' => '500.00'], $burgers);
    $drink = e2eProduct(['base_price' => '500.00'], $drinks);
    $user = User::factory()->create();

    $offer = Offer::factory()->forCategories()->create([
        'title' => 'Burger Ten',
        'type' => OfferType::Discounts,
        'offer_value' => '10% off',
        'is_active' => true,
    ]);
    $offer->categories()->sync([$burgers->id]);
    Coupon::factory()->fixed(50)->create(['code' => 'E2ESTACK50']);

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $burger->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)->postJson('/api/v1/cart/items', [
        'product_id' => $drink->id,
        'quantity' => 1,
    ])->assertSuccessful();

    $this->actingAs($user)
        ->postJson('/api/v1/cart/coupon', [
            'coupon_code' => 'E2ESTACK50',
            'branch_id' => $branch->id,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.offer_discount_amount', '50.00')
        ->assertJsonPath('data.coupon_discount_amount', '50.00')
        ->assertJsonPath('data.total', '900.00');

    $this->actingAs($user)
        ->postJson('/api/v1/checkout', [
            'branch_id' => $branch->id,
            'type' => OrderType::Takeaway->value,
            'payment_method' => PaymentMethod::Cod->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.discount_amount', '100.00')
        ->assertJsonPath('data.total', '900.00');
});

test('manager can verify payment for own branch only', function () {
    Storage::fake('local');

    $branchA = e2eBranch();
    $branchB = e2eBranch();
    $manager = User::factory()->manager($branchA)->create();
    $customer = User::factory()->create();

    $ownOrder = Order::factory()->for($customer)->create([
        'branch_id' => $branchA->id,
        'subtotal' => '1000.00',
        'grand_total' => '1000.00',
        'delivery_fee' => '0.00',
    ]);
    OrderItem::factory()->for($ownOrder)->create([
        'product_name' => 'Shake',
        'unit_price' => '1000.00',
        'quantity' => 1,
        'line_total' => '1000.00',
    ]);
    Payment::factory()->for($ownOrder)->create([
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
        'amount' => '1000.00',
    ]);
    PaymentProof::factory()->for($ownOrder->payment)->create();

    $otherOrder = Order::factory()->for($customer)->create([
        'branch_id' => $branchB->id,
        'subtotal' => '800.00',
        'grand_total' => '800.00',
        'delivery_fee' => '0.00',
    ]);
    Payment::factory()->for($otherOrder)->create([
        'method' => PaymentMethod::BankTransfer,
        'status' => PaymentStatus::Submitted,
        'amount' => '800.00',
    ]);
    PaymentProof::factory()->for($otherOrder->fresh('payment')->payment)->create();

    $this->actingAs($manager)
        ->postJson('/api/v1/admin/orders/'.$ownOrder->public_order_number.'/payment/verify')
        ->assertSuccessful()
        ->assertJsonPath('data.payment.status', PaymentStatus::Verified->value);

    $this->actingAs($manager)
        ->postJson('/api/v1/admin/orders/'.$otherOrder->public_order_number.'/payment/verify')
        ->assertForbidden();
});
