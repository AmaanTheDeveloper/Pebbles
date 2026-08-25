<?php

namespace App\Support;

use App\Models\SiteContent;

class AboutContent
{
    public const SITE_CONTENT_KEY = 'about';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'hero' => [
                'eyebrow' => 'Our story',
                'title_line1' => 'More Than',
                'title_accent' => 'Just a Treat',
                'subtitle' => 'At Pebbles, we believe every sip, scoop, and smile should feel special — premium desserts and refreshing drinks made for real moments.',
            ],
            'story' => [
                'eyebrow' => 'Our story',
                'title' => 'Born from a love of',
                'title_accent' => 'shakes, scoops & refreshment',
                'paragraphs' => [
                    'Pebbles started with a simple idea: create a dessert and beverage destination where quality taste and warm hospitality come together — without feeling formal or fussy.',
                    'From rich ice cream shakes to smooth scoops, layered faluda, creamy kulfi, and chilled Limca favorites, every recipe reflects our passion for freshness and the joy of sharing something delicious.',
                    'We are committed to memorable moments — whether you are cooling down solo, catching up with friends, or treating the family after a long day.',
                ],
            ],
            'philosophy' => [
                'eyebrow' => 'What we believe',
                'title' => 'Made for Moments',
                'subtitle' => 'Great taste, fresh ingredients, and experiences that feel relaxed, creative, and genuinely happy.',
                'items' => [
                    [
                        'icon' => 'Q',
                        'title' => 'Quality First',
                        'description' => 'We care about every ingredient, every recipe, and every detail that reaches your cup or cone.',
                    ],
                    [
                        'icon' => 'F',
                        'title' => 'Made Fresh',
                        'description' => 'Our drinks and desserts are prepared to deliver a fresh, satisfying experience every time.',
                    ],
                    [
                        'icon' => 'V',
                        'title' => 'Good Vibes',
                        'description' => 'Pebbles is about enjoying good food, good drinks, and good moments with the people you love.',
                    ],
                    [
                        'icon' => 'C',
                        'title' => 'Customer First',
                        'description' => 'Every visit should leave you happier than when you arrived — that is the Pebbles promise.',
                    ],
                ],
            ],
            'team' => [
                'eyebrow' => 'Meet the team',
                'title' => 'The People Behind Pebbles',
                'subtitle' => 'A passionate team focused on flavor, freshness, and making every guest feel welcome.',
                'roles' => [
                    [
                        'key' => 'founder',
                        'role' => 'Founder',
                        'description' => 'Passionate about building a place people love coming back to.',
                        'accent' => 'from-brand-orange/20 to-brand-orange/5',
                    ],
                    [
                        'key' => 'kitchen',
                        'role' => 'Creative Kitchen Team',
                        'description' => 'Focused on creating delicious shakes, scoops, and memorable treats.',
                        'accent' => 'from-brand-green/20 to-brand-green/5',
                    ],
                    [
                        'key' => 'service',
                        'role' => 'Service Team',
                        'description' => 'Dedicated to making every visit friendly, welcoming, and enjoyable.',
                        'accent' => 'from-brand-green-light/20 to-brand-cream',
                    ],
                ],
            ],
            'values' => [
                'eyebrow' => 'Core values',
                'title' => 'What We Stand For',
                'subtitle' => 'The principles that guide every scoop, sip, and smile at Pebbles.',
                'items' => [
                    ['icon' => 'Fr', 'title' => 'Freshness', 'description' => 'Cold, vibrant, and ready when you are.'],
                    ['icon' => 'Q', 'title' => 'Quality', 'description' => 'Thoughtful recipes and consistent standards.'],
                    ['icon' => 'Cr', 'title' => 'Creativity', 'description' => 'Flavors and combinations worth talking about.'],
                    ['icon' => 'H', 'title' => 'Hospitality', 'description' => 'Warm service that feels genuinely personal.'],
                    ['icon' => 'Co', 'title' => 'Consistency', 'description' => 'The Pebbles experience, visit after visit.'],
                    ['icon' => 'Ha', 'title' => 'Happiness', 'description' => 'Small treats that create big smiles.'],
                ],
            ],
            'ambience' => [
                'eyebrow' => 'The Pebbles experience',
                'title' => 'More Than a Place',
                'subtitle' => 'Pebbles is not only about what is in the cup or cone — it is about how it feels to be here.',
                'points' => [
                    [
                        'title' => 'Comfortable & modern',
                        'text' => 'A relaxed space designed for casual hangouts and sweet breaks.',
                    ],
                    [
                        'title' => 'Made for sharing',
                        'text' => 'Friends, family, and laughter over shakes, scoops, and refreshing sips.',
                    ],
                    [
                        'title' => 'More than a stop',
                        'text' => 'Pebbles is where dessert cravings meet a welcoming atmosphere.',
                    ],
                ],
            ],
            'brand_statement' => [
                'eyebrow' => 'Pebbles',
                'title' => 'Good Taste. Good Vibes.',
                'title_line2' => 'Good Memories.',
                'subtitle' => 'Whether it is a thick shake, a cold scoop, or a refreshing Limca — we are here to make the moment feel a little more special.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicPayload(): array
    {
        return self::merged();
    }

    /**
     * @return array<string, mixed>
     */
    public static function adminPayload(): array
    {
        return self::merged();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function update(array $payload): array
    {
        $merged = self::mergeRecursive(self::defaults(), self::sanitize($payload));

        SiteContent::query()->updateOrCreate(
            ['key' => self::SITE_CONTENT_KEY],
            ['content' => $merged],
        );

        PublicCatalogCache::forgetAbout();

        return $merged;
    }

    /**
     * @return array<string, mixed>
     */
    private static function merged(): array
    {
        $stored = SiteContent::query()->where('key', self::SITE_CONTENT_KEY)->value('content');

        if (! is_array($stored) || $stored === []) {
            return self::defaults();
        }

        return self::mergeRecursive(self::defaults(), $stored);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private static function sanitize(array $payload): array
    {
        $allowed = array_keys(self::defaults());

        return array_intersect_key($payload, array_flip($allowed));
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private static function mergeRecursive(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && self::isAssociative($value)) {
                $base[$key] = self::mergeRecursive($base[$key], $value);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function isAssociative(array $value): bool
    {
        if ($value === []) {
            return false;
        }

        return array_keys($value) !== range(0, count($value) - 1);
    }
}
