<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

class PublicCatalogCache
{
    public const TTL_SECONDS = 300;

    public const CATEGORIES_KEY = 'public.catalog.categories';

    public const BRANCHES_KEY = 'public.catalog.branches';

    public const BRANDING_KEY = 'public.catalog.branding';

    public const HOMEPAGE_KEY = 'public.catalog.homepage';

    public const ABOUT_KEY = 'public.catalog.about';

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, Closure $callback, ?int $ttlSeconds = null): mixed
    {
        return Cache::remember($key, $ttlSeconds ?? self::TTL_SECONDS, $callback);
    }

    public static function forgetCategories(): void
    {
        Cache::forget(self::CATEGORIES_KEY);
    }

    public static function forgetBranches(): void
    {
        Cache::forget(self::BRANCHES_KEY);
    }

    public static function forgetBranding(): void
    {
        Cache::forget(self::BRANDING_KEY);
    }

    public static function forgetHomepage(): void
    {
        Cache::forget(self::HOMEPAGE_KEY);
    }

    public static function forgetAbout(): void
    {
        Cache::forget(self::ABOUT_KEY);
    }

    public static function flush(): void
    {
        self::forgetCategories();
        self::forgetBranches();
        self::forgetBranding();
        self::forgetHomepage();
        self::forgetAbout();
    }
}
