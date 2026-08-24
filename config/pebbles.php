<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Order cancellation window (minutes)
    |--------------------------------------------------------------------------
    */

    'order_cancellation_minutes' => (int) env('ORDER_CANCELLATION_MINUTES', 20),

    'order_modification_minutes' => (int) env('ORDER_MODIFICATION_MINUTES', 20),

    'order_estimated_minutes' => (int) env('ORDER_ESTIMATED_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Payment proof storage
    |--------------------------------------------------------------------------
    */

    'payment_proof_disk' => env('PAYMENT_PROOF_DISK', 'local'),
    'payment_proof_directory' => 'payment-proofs',

    /*
    |--------------------------------------------------------------------------
    | Customer-facing payment methods
    |--------------------------------------------------------------------------
    |
    | Keys must match App\Enums\PaymentMethod values. Instructions are safe
    | customer-facing copy only — never put secrets or full account credentials here.
    |
    */

    'payment_methods' => [
        'cod' => [
            'label' => 'Cash on Delivery',
            'enabled' => true,
            'requires_proof' => false,
            'instructions' => 'Pay cash to the rider or at the counter when you receive your order. No online payment proof is required.',
        ],
        'easypaisa' => [
            'label' => 'EasyPaisa',
            'enabled' => true,
            'requires_proof' => true,
            'instructions' => 'Send the order total to the Pebbles EasyPaisa merchant account shown at checkout support, then upload a clear screenshot of the transfer.',
        ],
        'jazzcash' => [
            'label' => 'JazzCash',
            'enabled' => true,
            'requires_proof' => true,
            'instructions' => 'Send the order total to the Pebbles JazzCash merchant account shown at checkout support, then upload a clear screenshot of the transfer.',
        ],
        'bank_transfer' => [
            'label' => 'Bank Transfer',
            'enabled' => true,
            'requires_proof' => true,
            'instructions' => 'Transfer the order total to the Pebbles business bank account provided by support, then upload your transfer receipt (JPG, PNG, WEBP, or PDF, max 5 MB).',
        ],
    ],

];
