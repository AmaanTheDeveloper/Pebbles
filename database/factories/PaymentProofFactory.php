<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentProof;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentProof>
 */
class PaymentProofFactory extends Factory
{
    protected $model = PaymentProof::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'disk' => 'local',
            'path' => 'payment-proofs/'.fake()->uuid().'.jpg',
            'original_filename' => 'receipt.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 102400,
            'uploaded_at' => now(),
        ];
    }
}
