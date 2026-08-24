<?php

namespace App\Support;

use App\Models\Setting;

class ContactSettings
{
    public static function timezone(): string
    {
        return (string) Setting::getValue('contact_timezone', config('app.timezone'));
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicPayload(): array
    {
        $openingHours = Setting::getValue('contact_opening_hours');
        $timezone = self::timezone();

        return [
            'phone' => Setting::getValue('contact_phone'),
            'whatsapp' => Setting::getValue('contact_whatsapp'),
            'whatsapp_url' => self::whatsappUrl(Setting::getValue('contact_whatsapp')),
            'email' => Setting::getValue('contact_email'),
            'address' => Setting::getValue('contact_address'),
            'latitude' => Setting::getValue('contact_latitude'),
            'longitude' => Setting::getValue('contact_longitude'),
            'map_url' => Setting::getValue('contact_map_url'),
            'directions_url' => self::directionsUrl(
                Setting::getValue('contact_map_url'),
                Setting::getValue('contact_latitude'),
                Setting::getValue('contact_longitude'),
                Setting::getValue('contact_address'),
            ),
            'map_embed_url' => self::mapEmbedUrl(
                Setting::getValue('contact_latitude'),
                Setting::getValue('contact_longitude'),
                Setting::getValue('contact_address'),
            ),
            'timezone' => $timezone,
            'opening_hours' => OpeningHours::normalize(is_array($openingHours) ? $openingHours : null),
            'opening_hours_schedule' => OpeningHours::formattedSchedule(is_array($openingHours) ? $openingHours : null),
            'is_open_now' => OpeningHours::isOpenNow(
                is_array($openingHours) ? $openingHours : null,
                $timezone,
            ),
            'social' => array_filter([
                'instagram' => Setting::getValue('social_instagram'),
                'facebook' => Setting::getValue('social_facebook'),
                'tiktok' => Setting::getValue('social_tiktok'),
                'youtube' => Setting::getValue('social_youtube'),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminPayload(): array
    {
        return array_merge(self::publicPayload(), [
            'notification_email' => Setting::getValue('contact_notification_email'),
        ], OrderChangeSettings::adminPayload(), OrderEstimateSettings::adminPayload());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function update(array $payload): void
    {
        $stringFields = [
            'phone' => 'contact_phone',
            'whatsapp' => 'contact_whatsapp',
            'email' => 'contact_email',
            'address' => 'contact_address',
            'latitude' => 'contact_latitude',
            'longitude' => 'contact_longitude',
            'map_url' => 'contact_map_url',
            'timezone' => 'contact_timezone',
            'notification_email' => 'contact_notification_email',
            'social_instagram' => 'social_instagram',
            'social_facebook' => 'social_facebook',
            'social_tiktok' => 'social_tiktok',
            'social_youtube' => 'social_youtube',
        ];

        foreach ($stringFields as $input => $key) {
            if (! array_key_exists($input, $payload)) {
                continue;
            }

            $value = $payload[$input];
            Setting::setValue($key, $value === '' || $value === null ? null : $value, 'string');
        }

        if (array_key_exists('opening_hours', $payload)) {
            Setting::setValue('contact_opening_hours', $payload['opening_hours'], 'json');
        }

        OrderChangeSettings::update($payload);
        OrderEstimateSettings::update($payload);
    }

    public static function whatsappUrl(?string $number): ?string
    {
        if (! $number) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $number);

        return $digits ? 'https://wa.me/'.$digits : null;
    }

    public static function directionsUrl(?string $mapUrl, mixed $latitude, mixed $longitude, ?string $address): ?string
    {
        if ($mapUrl) {
            return $mapUrl;
        }

        if ($latitude !== null && $longitude !== null && $latitude !== '' && $longitude !== '') {
            return 'https://www.google.com/maps/search/?api=1&query='.$latitude.','.$longitude;
        }

        if ($address) {
            return 'https://www.google.com/maps/search/?api=1&query='.urlencode($address);
        }

        return null;
    }

    public static function mapEmbedUrl(mixed $latitude, mixed $longitude, ?string $address): ?string
    {
        if ($latitude !== null && $longitude !== null && $latitude !== '' && $longitude !== '') {
            return 'https://maps.google.com/maps?q='.$latitude.','.$longitude.'&z=15&output=embed';
        }

        if ($address) {
            return 'https://maps.google.com/maps?q='.urlencode($address).'&z=15&output=embed';
        }

        return null;
    }
}
