/** @typedef {'Food' | 'Interior' | 'Events' | 'Atmosphere'} GalleryCategory */

export const GALLERY_CATEGORIES = ['All', 'Food', 'Interior', 'Events', 'Atmosphere'];

const INTERIOR_PLACEHOLDERS = [
    {
        id: 'interior-counter',
        title: 'Pebbles Counter',
        description: 'A modern service counter built for friendly, fast orders.',
        gradient: 'from-brand-green to-brand-green-light',
    },
    {
        id: 'interior-seating',
        title: 'Cozy Seating',
        description: 'Comfortable spots to linger over shakes and scoops.',
        gradient: 'from-brand-green-light/90 to-brand-cream',
    },
    {
        id: 'interior-decor',
        title: 'Warm Decor',
        description: 'Fresh greens and playful accents throughout the space.',
        gradient: 'from-brand-orange to-[#ea580c]',
    },
];

const ATMOSPHERE_SLUGS = ['limca-flavors', 'gola-ganda', 'coffee'];
const FOOD_SLUGS = ['ice-cream-premium-flavours', 'ice-cream-shakes', 'falooda', 'gol-gappay', 'channa-chaat'];

/**
 * @param {object} product
 * @returns {GalleryCategory}
 */
function categorizeProduct(product) {
    const slug = product.category?.slug ?? '';

    if (ATMOSPHERE_SLUGS.includes(slug)) {
        return 'Atmosphere';
    }

    if (FOOD_SLUGS.includes(slug)) {
        return 'Food';
    }

    if (/kulfi|kulfa|shake|faluda|falooda|ice cream|scoops/i.test(product.name ?? '')) {
        return 'Food';
    }

    if (/limca|refresh|drink|gola|coffee/i.test(product.name ?? '')) {
        return 'Atmosphere';
    }

    if (product.is_featured) {
        return 'Atmosphere';
    }

    return 'Food';
}

/**
 * Build gallery items from catalog data + structured placeholders.
 * Designed for easy replacement when a dedicated gallery backend exists.
 *
 * @param {Array<object>} products
 * @param {Array<object>} offers
 * @returns {Array<object>}
 */
export function resolveGalleryItems(products = [], offers = []) {
    /** @type {Array<object>} */
    const items = [];
    const usedImageUrls = new Set();

    (products ?? [])
        .filter((product) => product.image?.url)
        .forEach((product) => {
            usedImageUrls.add(product.image.url);
            items.push({
                id: `product-${product.id}`,
                image: product.image.url,
                alt: product.image.alt_text || product.name,
                title: product.name,
                category: categorizeProduct(product),
                description: product.short_description || product.category?.name || 'From the Pebbles menu.',
            });
        });

    (offers ?? [])
        .filter((offer) => offer.image?.url)
        .forEach((offer) => {
            items.push({
                id: `offer-${offer.id}`,
                image: offer.image.url,
                alt: offer.title,
                title: offer.title,
                category: 'Events',
                description: offer.short_description || offer.type_label || 'A special Pebbles moment.',
            });
        });

    INTERIOR_PLACEHOLDERS.forEach((entry) => {
        items.push({
            id: entry.id,
            image: null,
            alt: entry.title,
            title: entry.title,
            category: 'Interior',
            description: entry.description,
            gradient: entry.gradient,
        });
    });

    const atmosphereCandidates = (products ?? []).filter(
        (product) => product.image?.url && product.is_featured && categorizeProduct(product) === 'Food',
    );

    atmosphereCandidates.slice(0, 4).forEach((product, index) => {
        items.push({
            id: `atmosphere-${product.id}`,
            image: product.image.url,
            alt: product.image.alt_text || product.name,
            title: index === 0 ? 'Pebbles Vibes' : `Shared moments · ${product.name}`,
            category: 'Atmosphere',
            description: 'Good food, good drinks, and memories made together.',
        });
    });

    if (!items.some((item) => item.category === 'Atmosphere')) {
        const fallback = (products ?? []).find(
            (product) => product.image?.url && !usedImageUrls.has(product.image.url),
        );

        if (fallback) {
            items.push({
                id: `atmosphere-fallback-${fallback.id}`,
                image: fallback.image.url,
                alt: fallback.image.alt_text || fallback.name,
                title: 'The Pebbles Experience',
                category: 'Atmosphere',
                description: 'Relaxed, refreshing, and made for good company.',
            });
        }
    }

    return items;
}

/**
 * @param {Array<object>} items
 * @param {string} category
 */
export function filterGalleryItems(items, category) {
    if (category === 'All') {
        return items;
    }

    return items.filter((item) => item.category === category);
}

/**
 * @param {number} index
 */
export function getGalleryLayoutVariant(index) {
    if (index === 0) {
        return 'featured';
    }

    if (index % 7 === 3) {
        return 'tall';
    }

    if (index % 5 === 2) {
        return 'wide';
    }

    return 'default';
}

export const GALLERY_LAYOUT_CLASSES = {
    featured: 'md:col-span-2 md:row-span-2',
    tall: 'md:row-span-2',
    wide: 'md:col-span-2',
    default: '',
};
