const ICE_CREAM_CATEGORY_SLUGS = ['ice-cream-premium-flavours'];
const GOLA_GANDA_CATEGORY_SLUGS = ['gola-ganda'];

export function categoryAllowsAddOns(categorySlug) {
    if (!categorySlug) {
        return false;
    }

    return (
        ICE_CREAM_CATEGORY_SLUGS.includes(categorySlug) ||
        GOLA_GANDA_CATEGORY_SLUGS.includes(categorySlug)
    );
}

export function addOnSectionHeading(categorySlug) {
    if (ICE_CREAM_CATEGORY_SLUGS.includes(categorySlug)) {
        return 'Choose Your Toppings';
    }

    if (GOLA_GANDA_CATEGORY_SLUGS.includes(categorySlug)) {
        return 'Customize Your Gola Ganda';
    }

    return null;
}

export function addOnSectionDescription(categorySlug) {
    if (ICE_CREAM_CATEGORY_SLUGS.includes(categorySlug)) {
        return 'Select optional ice cream toppings to customize your scoop.';
    }

    if (GOLA_GANDA_CATEGORY_SLUGS.includes(categorySlug)) {
        return 'Pick your favorite Gola Ganda toppings.';
    }

    return null;
}
