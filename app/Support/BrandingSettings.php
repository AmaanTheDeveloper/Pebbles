<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BrandingSettings
{
    public const LOGO_SETTING_KEY = 'site_logo_path';

    /**
     * @return array<string, mixed>
     */
    public static function publicPayload(): array
    {
        $path = self::logoPath();

        return [
            'logo' => $path ? [
                'url' => PublicMediaUrl::fromPath($path),
                'path' => $path,
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
        $path = Setting::getValue(self::LOGO_SETTING_KEY);

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return $path;
    }

    public static function storeLogo(UploadedFile $file): string
    {
        $previousPath = self::logoPath();
        $path = $file->store('branding', 'public');

        Setting::setValue(self::LOGO_SETTING_KEY, $path, 'string');
        self::deleteStoredLogo($previousPath);

        return $path;
    }

    public static function removeLogo(): void
    {
        $previousPath = self::logoPath();
        Setting::setValue(self::LOGO_SETTING_KEY, null, 'string');
        self::deleteStoredLogo($previousPath);
    }

    public static function deleteStoredLogo(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'branding/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
