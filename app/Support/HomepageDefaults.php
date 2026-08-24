<?php

namespace App\Support;

class HomepageDefaults
{
    /**
     * @return list<array{key: string, label: string, display_order: int, is_active: bool, settings: array<string, mixed>}>
     */
    public static function sections(): array
    {
        return [
            [
                'key' => 'hero',
                'label' => 'Hero',
                'display_order' => 1,
                'is_active' => true,
                'settings' => [
                    'badge' => 'Pebbles dessert bar',
                    'heading_line1' => 'Ice Cream. Shakes.',
                    'heading_accent' => 'Faluda. Kulfi.',
                    'subtitle' => 'Premium scoops, rich shakes, layered faluda, and creamy kulfi — order takeaway or delivery from your nearest branch.',
                    'primary_cta_text' => 'Order Online',
                    'primary_cta_url' => '/order',
                    'secondary_cta_text' => 'Explore Menu',
                    'secondary_cta_url' => '/menu',
                    'chips' => ['Ice Cream', 'Shakes', 'Faluda', 'Kulfi'],
                    'hero_product_id' => null,
                    'hero_image_path' => null,
                    'featured_label' => 'Featured treat',
                ],
            ],
            [
                'key' => 'favorites',
                'label' => 'Explore Favorites',
                'display_order' => 2,
                'is_active' => true,
                'settings' => [
                    'eyebrow' => 'Explore our favorites',
                    'title' => 'Scoops, sips & signature treats',
                    'description' => 'Ice cream, shakes, faluda, and special picks — start here, then order online.',
                ],
            ],
            [
                'key' => 'featured_ice_cream',
                'label' => 'Featured Ice Cream',
                'display_order' => 3,
                'is_active' => true,
                'settings' => [
                    'section_id' => 'featured-ice-cream',
                    'eyebrow' => 'Featured ice cream',
                    'title' => 'Scoops worth craving',
                    'description' => 'Handpicked ice cream favorites from the live menu.',
                    'cta_label' => 'All ice cream',
                    'category_slug' => 'ice-cream-premium-flavours',
                    'tone' => 'white',
                    'product_limit' => 8,
                ],
            ],
            [
                'key' => 'shakes',
                'label' => 'Shakes',
                'display_order' => 4,
                'is_active' => true,
                'settings' => [
                    'section_id' => 'shakes',
                    'eyebrow' => 'Shakes',
                    'title' => 'Thick. Creamy. Indulgent.',
                    'description' => 'Ice cream shakes made for long sips and happy afternoons.',
                    'cta_label' => 'All shakes',
                    'category_slug' => 'ice-cream-shakes',
                    'tone' => 'cream',
                    'product_limit' => 8,
                ],
            ],
            [
                'key' => 'signature_desserts',
                'label' => 'Signature Desserts',
                'display_order' => 5,
                'is_active' => true,
                'settings' => [
                    'eyebrow' => 'Signature desserts',
                    'title' => 'Shakes & Faluda',
                    'description' => 'Rich creamy shakes and colorful layered faluda — two irresistible favorites from Pebbles.',
                    'shake_cta' => 'Explore Shakes',
                    'faluda_cta' => 'Explore Faluda',
                ],
            ],
            [
                'key' => 'order_online',
                'label' => 'Order Online',
                'display_order' => 6,
                'is_active' => true,
                'settings' => [
                    'eyebrow' => 'Order online',
                    'title' => 'Your favorite dessert is just a click away',
                    'description' => 'Choose delivery or takeaway, pick your scoop or shake, and place your order in minutes.',
                ],
            ],
            [
                'key' => 'why_pebbles',
                'label' => 'Why Pebbles',
                'display_order' => 7,
                'is_active' => true,
                'settings' => [
                    'eyebrow' => 'Why Pebbles',
                    'title' => 'A dessert destination, not just a stop',
                    'description' => 'Premium treats, clear ordering, and flavors built around ice cream, shakes, faluda, and kulfi.',
                ],
            ],
            [
                'key' => 'offers',
                'label' => 'Offers',
                'display_order' => 8,
                'is_active' => true,
                'settings' => [
                    'eyebrow' => 'Offers',
                    'title' => 'Sweet deals, ready now',
                    'cta_label' => 'All offers',
                    'offer_limit' => 3,
                    'fallback_badge' => 'Treat yourself',
                    'fallback_title' => 'Cool down with ice cream, shakes, faluda & kulfi',
                    'fallback_cta_text' => 'Explore the menu',
                ],
            ],
            [
                'key' => 'gallery',
                'label' => 'Gallery Preview',
                'display_order' => 9,
                'is_active' => true,
                'settings' => [
                    'eyebrow' => 'Gallery',
                    'title' => 'A scoop of the vibe',
                    'description' => 'Ice cream, shakes, faluda, and kulfi from the live menu — tap any treat for details.',
                    'item_limit' => 6,
                ],
            ],
            [
                'key' => 'reviews',
                'label' => 'Reviews',
                'display_order' => 10,
                'is_active' => true,
                'settings' => [
                    'eyebrow' => 'What our customers say',
                    'title' => 'Real Pebbles moments',
                    'description' => 'Approved guest reviews from shakes, scoops, and visits at Pebbles.',
                    'review_limit' => 6,
                    'empty_title' => 'Be the first to share your Pebbles experience',
                    'empty_description' => 'Tell us about your favorite shake, scoop, or visit.',
                    'share_cta_text' => 'Share Your Experience',
                    'view_all_cta_text' => 'View All Reviews',
                ],
            ],
            [
                'key' => 'location',
                'label' => 'Location',
                'display_order' => 11,
                'is_active' => true,
                'settings' => [
                    'empty_eyebrow' => 'Location',
                    'empty_title' => 'Find a branch',
                    'empty_description' => 'Choose a branch from the header to see address, hours, and contact details.',
                    'card_eyebrow' => 'Location',
                    'visit_eyebrow' => 'Visit us',
                    'visit_title' => 'Come for dessert. Stay for more.',
                    'visit_description' => 'Ice cream, shakes, faluda, and kulfi at your nearest branch — switch locations anytime in the header.',
                    'directions_cta_text' => 'Get directions',
                ],
            ],
            [
                'key' => 'final_cta',
                'label' => 'Final CTA',
                'display_order' => 12,
                'is_active' => true,
                'settings' => [
                    'eyebrow' => 'Ready for something sweet?',
                    'title' => 'Scoop it. Sip it. Order it.',
                    'description' => 'Explore ice cream, shakes, faluda, and kulfi — then place your order online.',
                    'primary_cta_text' => 'Order Online',
                    'primary_cta_url' => '/order',
                    'secondary_cta_text' => 'Explore Menu',
                    'secondary_cta_url' => '/menu',
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function features(): array
    {
        return [
            [
                'title' => 'Fresh ingredients',
                'description' => 'Desserts prepared with care for flavor you can taste in every scoop and sip.',
                'icon_letter' => 'F',
                'accent' => 'from-brand-green/15 to-brand-green/5',
                'glow' => 'lift-card-green',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Rich flavors',
                'description' => 'Ice cream, shakes, faluda, and kulfi crafted for real dessert cravings.',
                'icon_letter' => 'R',
                'accent' => 'from-brand-orange/20 to-brand-orange/5',
                'glow' => 'lift-card-orange',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Easy ordering',
                'description' => 'Takeaway or delivery from your nearest branch — order online in a few taps.',
                'icon_letter' => 'E',
                'accent' => 'from-brand-green-light/20 to-brand-cream',
                'glow' => 'lift-card-green',
                'display_order' => 3,
                'is_active' => true,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function ctaCards(): array
    {
        return [
            [
                'title' => 'Order Now',
                'description' => 'Start with ice cream, shakes, faluda, or kulfi — then check out.',
                'link_url' => '/order',
                'tone' => 'orange',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Explore Menu',
                'description' => 'Browse every dessert category on the live Pebbles menu.',
                'link_url' => '/menu',
                'tone' => 'green',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Your Cart',
                'description' => 'Review your treats and continue when you are ready.',
                'link_url' => '/cart',
                'tone' => 'white',
                'display_order' => 3,
                'is_active' => true,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function showcaseItems(): array
    {
        return [
            [
                'pillar_key' => 'ice-cream',
                'category_id' => null,
                'label' => 'Ice Cream',
                'blurb' => 'Creamy handcrafted scoops and classic favorites.',
                'badge' => null,
                'accent' => 'orange',
                'is_special' => false,
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'pillar_key' => 'shakes',
                'category_id' => null,
                'label' => 'Shakes',
                'blurb' => 'Rich, creamy and made to satisfy every sweet craving.',
                'badge' => null,
                'accent' => 'green',
                'is_special' => false,
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'pillar_key' => 'faluda',
                'category_id' => null,
                'label' => 'Faluda',
                'blurb' => 'Layered, colorful and packed with classic dessert goodness.',
                'badge' => null,
                'accent' => 'orange',
                'is_special' => false,
                'display_order' => 3,
                'is_active' => true,
            ],
            [
                'pillar_key' => 'special-picks',
                'category_id' => null,
                'label' => 'Special Picks',
                'blurb' => 'Discover some of our most loved dessert favorites.',
                'badge' => 'Special',
                'accent' => 'orange',
                'is_special' => true,
                'display_order' => 4,
                'is_active' => true,
            ],
        ];
    }
}
