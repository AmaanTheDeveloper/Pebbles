<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BrandingSettings
{
    public const LOGO_SETTING_KEY = 'site_logo_path';

    public const FAVICON_SETTING_KEY = 'favicon_path';

    /**
     * @return array<string, mixed>
     */
    public static function publicPayload(): array
    {
        $logoPath = self::logoPath();
        $faviconPath = self::faviconPath();

        return [
            'logo' => $logoPath ? [
                'url' => PublicMediaUrl::fromPath($logoPath),
                'path' => $logoPath,
            ] : null,
            'favicon' => $faviconPath ? [
                'url' => PublicMediaUrl::fromPath($faviconPath),
                'path' => $faviconPath,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminPayload(): array
    {
        return self::publicPayload();
    }

    public static function logoPath(): ?string
    {
        return self::storedPath(self::LOGO_SETTING_KEY);
    }

    public static function faviconPath(): ?string
    {
        return self::storedPath(self::FAVICON_SETTING_KEY);
    }

    public static function storeLogo(UploadedFile $file): string
    {
        $previousPath = self::logoPath();
        $path = $file->store('branding', 'public');

        Setting::setValue(self::LOGO_SETTING_KEY, $path, 'string');
        self::deleteStoredBrandingFile($previousPath);
        PublicCatalogCache::forgetBranding();

        return $path;
    }

    public static function removeLogo(): void
    {
        $previousPath = self::logoPath();
        Setting::setValue(self::LOGO_SETTING_KEY, null, 'string');
        self::deleteStoredBrandingFile($previousPath);
        PublicCatalogCache::forgetBranding();
    }

    public static function storeFavicon(UploadedFile $file): string
    {
        $previousPath = self::faviconPath();
        $path = $file->store('branding', 'public');

        Setting::setValue(self::FAVICON_SETTING_KEY, $path, 'string');
        self::deleteStoredBrandingFile($previousPath);
        PublicCatalogCache::forgetBranding();

        return $path;
    }

    public static function removeFavicon(): void
    {
        $previousPath = self::faviconPath();
        Setting::setValue(self::FAVICON_SETTING_KEY, null, 'string');
        self::deleteStoredBrandingFile($previousPath);
        PublicCatalogCache::forgetBranding();
    }

    public static function deleteStoredLogo(?string $path): void
    {
        self::deleteStoredBrandingFile($path);
    }

    private static function storedPath(string $key): ?string
    {
        $path = Setting::getValue($key);

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return $path;
    }

    private static function deleteStoredBrandingFile(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'branding/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
