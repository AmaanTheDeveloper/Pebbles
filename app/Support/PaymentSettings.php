<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PaymentSettings
{
    public const SETTING_KEY = 'payment_settings';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'methods' => [
                'cod' => ['enabled' => true],
                'jazzcash' => ['enabled' => true],
                'bank_transfer' => ['enabled' => true],
                'easypaisa' => ['enabled' => false],
            ],
            'jazzcash' => [
                'business_name' => 'PEBBLES',
                'till_id' => '984159150',
                'qr_image_path' => null,
                'instructions' => 'Send the exact order amount to the Pebbles JazzCash Business Account using the Till ID or QR code. After completing the payment, upload a clear screenshot of the successful transaction as proof.',
            ],
            'bank_transfer' => [
                'bank_name' => '',
                'account_title' => '',
                'account_number' => '',
                'iban' => '',
                'branch_code' => '',
                'instructions' => 'Transfer the exact order amount to the bank account above, then upload your transfer receipt.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        self::ensureDefaults();

        $stored = Setting::getValue(self::SETTING_KEY);

        if (! is_array($stored)) {
            return self::defaults();
        }

        return self::normalize(array_replace_recursive(self::defaults(), $stored), $stored);
    }

    public static function ensureDefaults(): void
    {
        if (Setting::query()->where('key', self::SETTING_KEY)->exists()) {
            return;
        }

        Setting::setValue(self::SETTING_KEY, self::defaults(), 'json');
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private static function normalize(array $config, array $stored = []): array
    {
        $storedJazzcash = is_array($stored['jazzcash'] ?? null) ? $stored['jazzcash'] : [];
        $hasStoredBusinessName = array_key_exists('business_name', $storedJazzcash)
            && trim((string) $storedJazzcash['business_name']) !== '';

        if (
            ! $hasStoredBusinessName
            && trim((string) ($storedJazzcash['account_name'] ?? '')) !== ''
        ) {
            $config['jazzcash']['business_name'] = trim((string) $storedJazzcash['account_name']);
        }

        return $config;
    }

    public static function isMethodEnabled(string $methodId): bool
    {
        $config = config("pebbles.payment_methods.{$methodId}");

        if (! is_array($config)) {
            return false;
        }

        $settings = self::config();
        $override = $settings['methods'][$methodId]['enabled'] ?? null;

        if ($override !== null) {
            return (bool) $override;
        }

        return (bool) ($config['enabled'] ?? false);
    }

    public static function instructions(string $methodId): string
    {
        $settings = self::config();
        $config = config("pebbles.payment_methods.{$methodId}", []);
        $custom = '';

        if ($methodId === 'jazzcash') {
            $custom = trim((string) ($settings['jazzcash']['instructions'] ?? ''));
        }

        if ($methodId === 'bank_transfer') {
            $custom = trim((string) ($settings['bank_transfer']['instructions'] ?? ''));
        }

        if ($custom !== '') {
            return $custom;
        }

        return (string) ($config['instructions'] ?? '');
    }

    /**
     * @return array<string, string>
     */
    public static function customerDetails(string $methodId): array
    {
        $settings = self::config();

        if ($methodId === 'jazzcash') {
            return array_filter([
                'business_name' => trim((string) ($settings['jazzcash']['business_name'] ?? '')),
                'till_id' => trim((string) ($settings['jazzcash']['till_id'] ?? '')),
            ], fn (string $value): bool => $value !== '');
        }

        if ($methodId === 'bank_transfer') {
            return array_filter([
                'bank_name' => trim((string) ($settings['bank_transfer']['bank_name'] ?? '')),
                'account_title' => trim((string) ($settings['bank_transfer']['account_title'] ?? '')),
                'account_number' => trim((string) ($settings['bank_transfer']['account_number'] ?? '')),
                'iban' => trim((string) ($settings['bank_transfer']['iban'] ?? '')),
                'branch_code' => trim((string) ($settings['bank_transfer']['branch_code'] ?? '')),
            ], fn (string $value): bool => $value !== '');
        }

        return [];
    }

    public static function jazzcashQrImagePath(): ?string
    {
        $path = self::config()['jazzcash']['qr_image_path'] ?? null;

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return $path;
    }

    /**
     * @return array{url: string, path: string}|null
     */
    public static function jazzcashQrImagePayload(): ?array
    {
        $path = self::jazzcashQrImagePath();
        $url = PublicMediaUrl::fromPath($path);

        if ($url === null || $path === null) {
            return null;
        }

        return [
            'url' => $url,
            'path' => $path,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminPayload(): array
    {
        $config = self::config();
        $config['jazzcash'] = self::transformJazzcashForClient($config['jazzcash'] ?? []);

        return $config;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function update(array $payload): array
    {
        self::ensureDefaults();

        $current = self::config();
        $next = self::normalize(array_replace_recursive($current, $payload), array_replace_recursive($current, $payload));

        Setting::setValue(self::SETTING_KEY, $next, 'json');

        return self::adminPayload();
    }

    public static function storeJazzcashQrImage(UploadedFile $file): string
    {
        $config = self::config();
        $previousPath = $config['jazzcash']['qr_image_path'] ?? null;
        $path = $file->store('payments/jazzcash', 'public');

        $config['jazzcash']['qr_image_path'] = $path;
        Setting::setValue(self::SETTING_KEY, $config, 'json');
        self::deleteStoredJazzcashQrImage($previousPath);

        return $path;
    }

    public static function removeJazzcashQrImage(): void
    {
        $config = self::config();
        $previousPath = $config['jazzcash']['qr_image_path'] ?? null;
        $config['jazzcash']['qr_image_path'] = null;
        Setting::setValue(self::SETTING_KEY, $config, 'json');
        self::deleteStoredJazzcashQrImage($previousPath);
    }

    public static function deleteStoredJazzcashQrImage(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'payments/jazzcash/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  array<string, mixed>  $jazzcash
     * @return array<string, mixed>
     */
    private static function transformJazzcashForClient(array $jazzcash): array
    {
        $path = $jazzcash['qr_image_path'] ?? null;
        $url = PublicMediaUrl::fromPath(is_string($path) ? $path : null);

        $jazzcash['qr_image'] = $url ? ['url' => $url, 'path' => $path] : null;

        unset($jazzcash['account_name'], $jazzcash['number']);

        return $jazzcash;
    }
}
