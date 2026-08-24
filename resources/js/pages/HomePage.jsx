import { useMemo } from 'react';
import { useOutletContext } from 'react-router-dom';
import { fetchCategories, fetchOffers, fetchProducts } from '../api/catalog';
import { fetchGalleryItems } from '../api/gallery';
import { activeSections, fetchHomepageContent, sectionSettings } from '../api/homepage';
import ErrorState from '../components/storefront/ErrorState';
import HomeFavorites from '../components/storefront/home/HomeFavorites';
import HomeFaludaKulfi from '../components/storefront/home/HomeFaludaKulfi';
import HomeFinalCta from '../components/storefront/home/HomeFinalCta';
import HomeGallery from '../components/storefront/home/HomeGallery';
import HomeHero from '../components/storefront/home/HomeHero';
import HomeLocation from '../components/storefront/home/HomeLocation';
import HomeOffers from '../components/storefront/home/HomeOffers';
import HomeOrderOnline from '../components/storefront/home/HomeOrderOnline';
import HomeProductStrip from '../components/storefront/home/HomeProductStrip';
import HomeReviews from '../components/storefront/home/HomeReviews';
import HomeServices from '../components/storefront/home/HomeServices';
import HomeWelcomePopup from '../components/storefront/HomeWelcomePopup';
import HomePageSkeleton from '../components/storefront/HomePageSkeleton';
import { useCatalogQuery } from '../hooks/useCatalog';
import { filterByCategorySlug, resolveDessertPillars, resolveHomepageShowcaseCards } from '../lib/homeDessertCategories';

function productsForCategorySlug(products, categories, slug) {
    const category = categories.find((entry) => entry.slug === slug);

    if (!category) {
        return filterByCategorySlug(products, slug);
    }

    return (products ?? []).filter(
        (product) => product.category?.slug === category.slug || product.category?.id === category.id,
    );
}

export default function HomePage() {
    const { selectedBranch, branches } = useOutletContext() ?? {};

    const {
        data: homepage,
        loading: homepageLoading,
        error: homepageError,
        reload: reloadHomepage,
    } = useCatalogQuery(fetchHomepageContent, []);

    const {
        data: categories,
        error: categoriesError,
        reload: reloadCategories,
    } = useCatalogQuery(fetchCategories, []);

    const {
        data: products,
        error: productsError,
        reload: reloadProducts,
    } = useCatalogQuery(() => fetchProducts(), []);

    const featured = useMemo(
        () => (products ?? []).filter((product) => product.is_featured),
        [products],
    );

    const {
        data: offers,
        error: offersError,
        reload: reloadOffers,
    } = useCatalogQuery(fetchOffers, []);

    const {
        data: galleryItems,
        error: galleryError,
        reload: reloadGallery,
    } = useCatalogQuery(fetchGalleryItems, []);

    const error =
        homepageError ||
        categoriesError ||
        productsError ||
        offersError ||
        galleryError;

    if (homepageLoading && !homepage) {
        return <HomePageSkeleton />;
    }

    if (error && !homepage && !categories?.length && !products?.length) {
        return (
            <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <ErrorState
                    message={error.message}
                    onRetry={() => {
                        reloadHomepage();
                        reloadCategories();
                        reloadProducts();
                        reloadOffers();
                        reloadGallery();
                    }}
                />
            </div>
        );
    }

    const sections = activeSections(homepage?.sections ?? []);
    const featuredList = featured ?? [];
    const productList = products ?? [];
    const categoryList = categories ?? [];
    const pillars = resolveDessertPillars(categoryList, productList);
    const showcaseCards = resolveHomepageShowcaseCards(
        homepage?.showcase_items ?? [],
        categoryList,
        productList,
        featuredList,
    );
    const iceCream = pillars.find((pillar) => pillar.key === 'ice-cream');
    const shakes = pillars.find((pillar) => pillar.key === 'shakes');
    const faluda = pillars.find((pillar) => pillar.key === 'faluda');

    const iceCreamSettings = sectionSettings(sections, 'featured_ice_cream');
    const iceCreamProducts = (() => {
        const slug = iceCreamSettings.category_slug ?? iceCream?.category?.slug ?? 'ice-cream-premium-flavours';
        const fromCategory = productsForCategorySlug(productList, categoryList, slug);
        if (!fromCategory.length) {
            return featuredList;
        }
        const featuredIds = new Set(featuredList.map((item) => item.id));
        const featuredFirst = fromCategory.filter((item) => featuredIds.has(item.id));
        const rest = fromCategory.filter((item) => !featuredIds.has(item.id));
        return [...featuredFirst, ...rest];
    })();

    const heroSettings = sectionSettings(sections, 'hero');
    const heroProduct =
        heroSettings.hero_product ??
        featuredList[0] ??
        iceCreamProducts.find((item) => item.image?.url) ??
        productList.find((item) => item.image?.url) ??
        productList[0] ??
        null;

    const branch = selectedBranch ?? branches?.[0] ?? null;
    const shakesSettings = sectionSettings(sections, 'shakes');
    const shakesSlug = shakesSettings.category_slug ?? shakes?.category?.slug ?? 'ice-cream-shakes';
    const shakesProducts = productsForCategorySlug(productList, categoryList, shakesSlug);

    const renderSection = (section) => {
        const settings = section.settings ?? {};

        switch (section.key) {
            case 'hero':
                return (
                    <HomeHero
                        key={section.key}
                        heroProduct={heroProduct}
                        pillars={showcaseCards}
                        content={settings}
                    />
                );
            case 'favorites':
                return (
                    <HomeFavorites
                        key={section.key}
                        cards={showcaseCards}
                        content={settings}
                    />
                );
            case 'featured_ice_cream':
                return (
                    <HomeProductStrip
                        key={section.key}
                        id="featured-ice-cream"
                        eyebrow="Featured ice cream"
                        title="Scoops worth craving"
                        description="Handpicked ice cream favorites from the live menu."
                        products={iceCreamProducts}
                        href={iceCream?.href ?? '/menu'}
                        ctaLabel="All ice cream"
                        tone={settings.tone ?? 'white'}
                        content={settings}
                    />
                );
            case 'shakes':
                return (
                    <HomeProductStrip
                        key={section.key}
                        id="shakes"
                        eyebrow="Shakes"
                        title="Thick. Creamy. Indulgent."
                        description="Ice cream shakes made for long sips and happy afternoons."
                        products={shakesProducts}
                        href={shakes?.href ?? '/menu'}
                        ctaLabel="All shakes"
                        tone={settings.tone ?? 'cream'}
                        content={settings}
                    />
                );
            case 'signature_desserts':
                return (
                    <HomeFaludaKulfi
                        key={section.key}
                        shakes={shakes}
                        faluda={faluda}
                        avoidImageUrls={showcaseCards.map((card) => card.coverImage).filter(Boolean)}
                        content={settings}
                    />
                );
            case 'order_online':
                return (
                    <HomeOrderOnline
                        key={section.key}
                        cards={homepage?.cta_cards ?? []}
                        content={settings}
                    />
                );
            case 'why_pebbles':
                return (
                    <HomeServices
                        key={section.key}
                        features={homepage?.features ?? []}
                        content={settings}
                    />
                );
            case 'offers':
                return (
                    <HomeOffers
                        key={section.key}
                        offers={offers ?? []}
                        featuredProduct={featuredList[0] ?? productList[0] ?? null}
                        content={settings}
                    />
                );
            case 'gallery':
                return (
                    <HomeGallery
                        key={section.key}
                        items={galleryItems ?? []}
                        products={productList}
                        content={settings}
                    />
                );
            case 'reviews':
                return <HomeReviews key={section.key} content={settings} />;
            case 'location':
                return <HomeLocation key={section.key} branch={branch} content={settings} />;
            case 'final_cta':
                return <HomeFinalCta key={section.key} content={settings} />;
            default:
                return null;
        }
    };

    return (
        <div>
            {sections.map((section) => renderSection(section))}
            <HomeWelcomePopup />
        </div>
    );
}
