<?php

test('production preflight command runs and reports checks', function () {
    $this->artisan('pebbles:preflight')
        ->expectsOutputToContain('Pebbles production preflight')
        ->expectsOutputToContain('APP_DEBUG')
        ->expectsOutputToContain('MAIL')
        ->expectsOutputToContain('QUEUE')
        ->expectsOutputToContain('PAYMENT_PROOF_DISK')
        ->assertSuccessful();
});

test('production preflight fails when payment proof disk is public', function () {
    config(['pebbles.payment_proof_disk' => 'public']);

    $this->artisan('pebbles:preflight')
        ->expectsOutputToContain('Payment proof disk "public" is public')
        ->assertFailed();
});
