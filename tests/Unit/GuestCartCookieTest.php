<?php

use App\Support\GuestCartCookie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

test('read returns guest cart token from request cookie', function () {
    $request = Request::create('/');
    $request->cookies->set(GuestCartCookie::NAME, 'guest-token-abc');

    expect(GuestCartCookie::read($request))->toBe('guest-token-abc');
});

test('read returns null for missing or empty cookie', function () {
    $missing = Request::create('/');
    $empty = Request::create('/');
    $empty->cookies->set(GuestCartCookie::NAME, '');

    expect(GuestCartCookie::read($missing))->toBeNull()
        ->and(GuestCartCookie::read($empty))->toBeNull();
});

test('issue queues cookie and returns provided or generated token', function () {
    $token = GuestCartCookie::issue('fixed-guest-token');

    expect($token)->toBe('fixed-guest-token')
        ->and(Cookie::hasQueued(GuestCartCookie::NAME))->toBeTrue();

    $generated = GuestCartCookie::issue();

    expect($generated)->not->toBeEmpty()
        ->and($generated)->not->toBe('fixed-guest-token');
});

test('clear queues a forgotten guest cart cookie', function () {
    GuestCartCookie::clear();

    expect(Cookie::hasQueued(GuestCartCookie::NAME))->toBeTrue();
});
