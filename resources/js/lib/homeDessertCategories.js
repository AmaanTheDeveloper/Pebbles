/**
 * Resolve dessert pillars from live catalog categories/products.
 * Uses existing slugs; does not invent catalog data.
 */

const PILLARS = [
    {
        key: 'ice-cream',
        label: 'Ice Cream',
        blurb: 'Creamy handcrafted scoops and classic favorites.',
        categorySlugs: ['ice-cream-premium-flavours'],
        accent: 'orange',
    },
    {
        key: 'shakes',
        label: 'Shakes',
        blurb: 'Rich, creamy and made to satisfy every sweet craving.',
        categorySlugs: ['ice-cream-shakes'],
        accent: 'green',
    },
    {
        key: 'faluda',
        label: 'Faluda',
        blurb: 'Layered, colorful and packed with classic dessert goodness.',
        categorySlugs: ['falooda'],
        accent: 'orange',
    },
    {
        key: 'kulfi',
        label: 'Kulfi',
        blurb: 'Traditional creamy frozen delight.',
        categorySlugs: [],
        namePattern: /kulfi|kulfa/i,
        accent: 'green',
    },
];

function productsForCategory(products, category) {
    if (!category) {
        return [];
    }

    return (products ?? []).filter(
        (product) => product.category?.slug === category.slug || product.category?.id === category.id,
    );
}

function productsByNamePattern(products, pattern) {
    return (products ?? []).filter(
        (product) => pattern.test(product.name ?? '') || pattern.test(product.slug ?? ''),
    );
}

function uniqueById(items) {
    return (items ?? []).filter(
        (item, index, list) => list.findIndex((candidate) => candidate.id === item.id) === index,
    );
}

/**
 * @param {Array<{id:number,name:string,slug:string,image?:{url?:string}}>} categories
 * @param {Array<object>} products
 */
export function resolveDessertPillars(categories = [], products = []) {
    return PILLARS.map((pillar) => {
        const matchedCategory =
            pillar.categorySlugs
                .map((slug) => categories.find((category) => category.slug === slug))
                .find(Boolean) ?? null;

        let pillarProducts = matchedCategory ? productsForCategory(products, matchedCategory) : [];

        if (pillar.namePattern) {
            const named = productsByNamePattern(products, pillar.namePattern);
            if (named.length) {
                pillarProducts = named;
            }
        }

        const coverProduct = pillarProducts.find((product) => product.image?.url) ?? pillarProducts[0] ?? null;
        const href = matchedCategory
            ? `/menu/${matchedCategory.slug}`
            : coverProduct
              ? `/product/${coverProduct.slug}`
              : '/menu';

        return {
            ...pillar,
            category: matchedCategory,
            products: pillarProducts,
            coverImage: coverProduct?.image?.url ?? null,
            coverAlt: coverProduct?.image?.alt_text || coverProduct?.name || pillar.label,
            href,
            available: pillarProducts.length > 0 || matchedCategory !== null,
        };
    });
}

/**
 * Homepage Explore Favorites row: Ice Cream, Shakes, Faluda, Special Picks.
 * Kulfi stays available elsewhere (signature section, menu, nav) — omitted here to avoid a duplicate Faluda lookalike image.
 *
 * @param {Array<object>} categories
 * @param {Array<object>} products
 * @param {Array<object>} featured
 */
export function resolveShowcaseFavorites(categories = [], products = [], featured = []) {
    const pillars = resolveDessertPillars(categories, products);
    const iceCream = pillars.find((pillar) => pillar.key === 'ice-cream');
    const shakes = pillars.find((pillar) => pillar.key === 'shakes');
    const faluda = pillars.find((pillar) => pillar.key === 'faluda');

    const usedImageUrls = new Set(
        [iceCream?.coverImage, shakes?.coverImage, faluda?.coverImage].filter(Boolean),
    );

    const preferredCategorySlugs = ['gola-ganda', 'limca-flavors', 'coffee', 'gol-gappay', 'channa-chaat'];

    const specialPool = uniqueById([
        ...(products ?? []).filter((product) => preferredCategorySlugs.includes(product.category?.slug)),
        ...(featured ?? []),
        ...(products ?? []).filter((product) => product.is_featured),
        ...(products ?? []),
    ]);

    const specialProduct =
        specialPool.find((product) => product.image?.url && !usedImageUrls.has(product.image.url)) ??
        specialPool.find((product) => product.image?.url) ??
        null;

    const specialPicks = {
        key: 'special-picks',
        label: 'Special Picks',
        blurb: 'Discover some of our most loved dessert favorites.',
        accent: 'orange',
        badge: 'Special',
        category: null,
        products: uniqueById([...(featured ?? []), ...(products ?? []).filter((product) => product.is_featured)]).slice(
            0,
            8,
        ),
        coverImage: specialProduct?.image?.url ?? null,
        coverAlt: specialProduct?.image?.alt_text || specialProduct?.name || 'Special Picks',
        href: '/menu',
        available: true,
        isSpecial: true,
    };

    return [iceCream, shakes, faluda, specialPicks].filter((card) => card && card.available !== false);
}

export function filterByCategorySlug(products, slug) {
    return (products ?? []).filter((product) => product.category?.slug === slug);
}

const PILLAR_CATEGORY_SLUGS = {
    'ice-cream': ['ice-cream-premium-flavours'],
    shakes: ['ice-cream-shakes'],
    faluda: ['falooda'],
    kulfi: [],
};

const PILLAR_NAME_PATTERNS = {
    kulfi: /kulfi|kulfa/i,
};

/**
 * Build homepage favorite cards from admin showcase config + live catalog data.
 *
 * @param {Array<object>} showcaseItems
 * @param {Array<object>} categories
 * @param {Array<object>} products
 * @param {Array<object>} featured
 */
export function resolveHomepageShowcaseCards(showcaseItems = [], categories = [], products = [], featured = []) {
    const pillars = resolveDessertPillars(categories, products);
    const usedImageUrls = new Set();

    return (showcaseItems ?? [])
        .map((item) => {
            const pillar = pillars.find((entry) => entry.key === item.pillar_key) ?? null;

            if (item.pillar_key === 'special-picks' || item.is_special) {
                const preferredCategorySlugs = ['gola-ganda', 'limca-flavors', 'coffee', 'gol-gappay', 'channa-chaat'];
                const specialPool = uniqueById([
                    ...(products ?? []).filter((product) => preferredCategorySlugs.includes(product.category?.slug)),
                    ...(featured ?? []),
                    ...(products ?? []).filter((product) => product.is_featured),
                    ...(products ?? []),
                ]);
                const specialProduct =
                    specialPool.find((product) => product.image?.url && !usedImageUrls.has(product.image.url)) ??
                    specialPool.find((product) => product.image?.url) ??
                    null;

                if (specialProduct?.image?.url) {
                    usedImageUrls.add(specialProduct.image.url);
                }

                return {
                    key: item.pillar_key || 'special-picks',
                    label: item.label || 'Special Picks',
                    blurb: item.blurb || 'Discover some of our most loved dessert favorites.',
                    accent: item.accent || 'orange',
                    badge: item.badge || 'Special',
                    category: item.category ?? null,
                    products: uniqueById([...(featured ?? []), ...(products ?? []).filter((product) => product.is_featured)]).slice(0, 8),
                    coverImage: specialProduct?.image?.url ?? null,
                    coverAlt: specialProduct?.image?.alt_text || specialProduct?.name || item.label || 'Special Picks',
                    href: item.category?.slug ? `/menu/${item.category.slug}` : '/menu',
                    available: true,
                    isSpecial: true,
                };
            }

            const categoryFromAdmin = item.category
                ? categories.find((category) => category.id === item.category.id || category.slug === item.category.slug)
                : null;
            const categorySlug = categoryFromAdmin?.slug ?? PILLAR_CATEGORY_SLUGS[item.pillar_key]?.[0] ?? null;
            const matchedCategory =
                categoryFromAdmin ??
                (categorySlug ? categories.find((category) => category.slug === categorySlug) : null) ??
                pillar?.category ??
                null;

            let pillarProducts = matchedCategory ? productsForCategory(products, matchedCategory) : pillar?.products ?? [];

            const namePattern = PILLAR_NAME_PATTERNS[item.pillar_key];
            if (namePattern) {
                const named = productsByNamePattern(products, namePattern);
                if (named.length) {
                    pillarProducts = named;
                }
            }

            const coverProduct = pillarProducts.find((product) => product.image?.url) ?? pillarProducts[0] ?? null;
            const coverImage = coverProduct?.image?.url ?? pillar?.coverImage ?? null;

            if (coverImage) {
                usedImageUrls.add(coverImage);
            }

            const href = matchedCategory
                ? `/menu/${matchedCategory.slug}`
                : coverProduct
                  ? `/product/${coverProduct.slug}`
                  : pillar?.href ?? '/menu';

            return {
                key: item.pillar_key,
                label: item.label || pillar?.label || matchedCategory?.name || 'Category',
                blurb: item.blurb || pillar?.blurb || '',
                accent: item.accent || pillar?.accent || 'orange',
                badge: item.badge ?? null,
                category: matchedCategory,
                products: pillarProducts,
                coverImage,
                coverAlt: coverProduct?.image?.alt_text || coverProduct?.name || item.label || pillar?.label,
                href,
                available: pillarProducts.length > 0 || matchedCategory !== null || pillar?.available,
                isSpecial: Boolean(item.is_special),
            };
        })
        .filter((card) => card.available !== false);
}
