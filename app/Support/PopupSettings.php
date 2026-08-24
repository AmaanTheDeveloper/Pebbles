<?php

namespace App\Support;

use App\Models\Offer;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PopupSettings
{
    public const SETTING_KEY = 'popup_settings';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'is_active' => false,
            'type' => 'welcome_offer',
            'heading' => 'Welcome to Pebbles',
            'message' => 'Something Delicious Is Waiting For You',
            'description' => 'Enjoy our latest special offer and make your next Pebbles moment even better.',
            'image_path' => null,
            'badge_text' => 'Limited-time offer',
            'button_text' => 'Order Online',
            'button_url' => '/order',
            'delay_ms' => 700,
            'frequency' => 'session',
            'offer_id' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        $stored = Setting::getValue(self::SETTING_KEY);

        if (! is_array($stored)) {
            return self::defaults();
        }

        return array_merge(self::defaults(), $stored);
    }

    public static function ensureDefaults(): void
    {
        if (Setting::query()->where('key', self::SETTING_KEY)->exists()) {
            return;
        }

        Setting::setValue(self::SETTING_KEY, self::defaults(), 'json');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function publicPayload(): ?array
    {
        self::ensureDefaults();
        $config = self::config();

        if (! ($config['is_active'] ?? false)) {
            return null;
        }

        return self::transformForClient($config, false);
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminPayload(): array
    {
        self::ensureDefaults();

        return self::transformForClient(self::config(), true);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function update(array $payload): array
    {
        self::ensureDefaults();
        $config = self::config();

        $fields = [
            'is_active',
            'type',
            'heading',
            'message',
            'description',
            'badge_text',
            'button_text',
            'button_url',
            'delay_ms',
            'frequency',
            'offer_id',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $payload)) {
                $config[$field] = $payload[$field];
            }
        }

        if (array_key_exists('offer_id', $payload)) {
            $config['offer_id'] = $payload['offer_id'] ? (int) $payload['offer_id'] : null;
        }

        if (array_key_exists('delay_ms', $payload)) {
            $config['delay_ms'] = max(0, min(5000, (int) $payload['delay_ms']));
        }

        Setting::setValue(self::SETTING_KEY, $config, 'json');

        return self::adminPayload();
    }

    public static function storeImage(UploadedFile $file): string
    {
        $config = self::config();
        $previousPath = $config['image_path'] ?? null;
        $path = $file->store('popup', 'public');

        $config['image_path'] = $path;
        Setting::setValue(self::SETTING_KEY, $config, 'json');
        self::deleteStoredImage($previousPath);

        return $path;
    }

    public static function removeImage(): void
    {
        $config = self::config();
        $previousPath = $config['image_path'] ?? null;
        $config['image_path'] = null;
        Setting::setValue(self::SETTING_KEY, $config, 'json');
        self::deleteStoredImage($previousPath);
    }

    public static function deleteStoredImage(?string $path): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'popup/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private static function transformForClient(array $config, bool $includeMeta): array
    {
        $offer = null;
        if (! empty($config['offer_id'])) {
            $offerModel = Offer::query()->currentlyAvailable()->find($config['offer_id']);
            if ($offerModel) {
                $offer = [
                    'id' => $offerModel->id,
                    'title' => $offerModel->title,
                    'short_description' => $offerModel->short_description,
                    'offer_value' => $offerModel->offer_value,
                    'type_label' => $offerModel->type?->label(),
                    'image' => $offerModel->imageUrl() ? ['url' => $offerModel->imageUrl()] : null,
                    'cta_label' => $offerModel->cta_label,
                    'cta_url' => $offerModel->cta_url,
                ];
            }
        }

        $imagePath = $config['image_path'] ?? null;
        $imageUrl = PublicMediaUrl::fromPath($imagePath);

        if (! $imageUrl && is_array($offer) && ! empty($offer['image']['url'])) {
            $imageUrl = $offer['image']['url'];
        }

        $heading = self::filled($config['heading'] ?? null)
            ? $config['heading']
            : (is_array($offer) ? ($offer['title'] ?? null) : null);
        $message = self::filled($config['message'] ?? null)
            ? $config['message']
            : (is_array($offer) ? ($offer['short_description'] ?? null) : null);
        $description = self::filled($config['description'] ?? null) ? $config['description'] : null;
        $badgeText = self::filled($config['badge_text'] ?? null)
            ? $config['badge_text']
            : (is_array($offer) ? ($offer['offer_value'] ?? null) : null);
        $buttonText = self::filled($config['button_text'] ?? null)
            ? $config['button_text']
            : (is_array($offer) ? ($offer['cta_label'] ?? null) : null);
        $buttonUrl = self::filled($config['button_url'] ?? null)
            ? $config['button_url']
            : (is_array($offer) ? ($offer['cta_url'] ?? null) : null);

        $payload = [
            'is_active' => (bool) ($config['is_active'] ?? false),
            'type' => $config['type'] ?? 'welcome_offer',
            'heading' => $heading,
            'message' => $message,
            'description' => $description,
            'badge_text' => $badgeText,
            'button_text' => $buttonText,
            'button_url' => $buttonUrl,
            'delay_ms' => (int) ($config['delay_ms'] ?? 700),
            'frequency' => $config['frequency'] ?? 'session',
            'offer_id' => $config['offer_id'] ?? null,
            'image' => $imageUrl ? ['url' => $imageUrl, 'path' => $imagePath] : null,
            'offer' => $offer,
        ];

        if ($includeMeta) {
            $payload['updated_at'] = Setting::query()->where('key', self::SETTING_KEY)->value('updated_at');
        }

        return $payload;
    }

    private static function filled(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }
}
