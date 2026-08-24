<?php

use App\Models\Payment;
use App\Models\PaymentProof;
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
