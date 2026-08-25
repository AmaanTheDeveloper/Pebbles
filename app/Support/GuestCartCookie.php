<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

final class GuestCartCookie
{
    public const NAME = 'pebbles_guest_cart';

    public const LIFETIME_MINUTES = 60 * 24 * 30;

    public static function read(Request $request): ?string
    {
        $token = $request->cookie(self::NAME);

        return is_string($token) && $token !== '' ? $token : null;
    }

    public static function issue(?string $token = null): string
    {
        $token ??= (string) Str::uuid();

        Cookie::queue(cookie(
            self::NAME,
            $token,
            self::LIFETIME_MINUTES,
            '/',
            null,
            config('session.secure'),
            true,
            false,
            'lax',
        ));

        return $token;
    }

    public static function clear(): void
    {
        Cookie::queue(Cookie::forget(self::NAME));
    }
}
