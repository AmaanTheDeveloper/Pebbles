import { fetchCategories, fetchProducts } from '../api/catalog';
import AboutAmbience from '../components/storefront/about/AboutAmbience';
import AboutBrandStatement from '../components/storefront/about/AboutBrandStatement';
import AboutCta from '../components/storefront/about/AboutCta';
import AboutHero from '../components/storefront/about/AboutHero';
import AboutPhilosophy from '../components/storefront/about/AboutPhilosophy';
import AboutSpecialties from '../components/storefront/about/AboutSpecialties';
import AboutStory from '../components/storefront/about/AboutStory';
import AboutTeam from '../components/storefront/about/AboutTeam';
import AboutValues from '../components/storefront/about/AboutValues';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import { useCatalogQuery } from '../hooks/useCatalog';
import { resolveDessertPillars } from '../lib/homeDessertCategories';

function resolveAboutSpecialties(categories = [], products = []) {
    const pillars = resolveDessertPillars(categories, products);
    const shakes = pillars.find((pillar) => pillar.key === 'shakes');
    const iceCream = pillars.find((pillar) => pillar.key === 'ice-cream');
    const limcaCategory = categories.find((category) => category.slug === 'limca-flavors');
    const limcaProducts = products.filter((product) => product.category?.slug === 'limca-flavors');
    const limcaCover =
        limcaProducts.find((product) => product.image?.url) ??
        limcaProducts[0] ??
        null;

    const limca = {
        key: 'limca',
        label: 'Limca & Refreshing Drinks',
        blurb: 'Fresh, chilled, and energizing — the perfect cool-down on a warm day.',
        coverImage: limcaCover?.image?.url ?? null,
        coverAlt: limcaCover?.image?.alt_text || limcaCover?.name || 'Limca & refreshing drinks',
        href: limcaCategory ? `/menu/${limcaCategory.slug}` : '/menu',
        accent: 'green',
        available: Boolean(limcaCategory || limcaProducts.length),
    };

    return [shakes, iceCream, limca].filter((item) => item && item.available !== false);
}

function pickImage(...candidates) {
    for (const candidate of candidates) {
        if (candidate?.image?.url) {
            return {
                url: candidate.image.url,
                alt: candidate.image.alt_text || candidate.name,
            };
        }
    }

    return { url: null, alt: null };
}

export default function AboutPage() {
    const {
        data: categories,
        loading: categoriesLoading,
        error: categoriesError,
        reload: reloadCategories,
    } = useCatalogQuery(fetchCategories, []);

    const {
        data: featured,
        loading: featuredLoading,
        error: featuredError,
        reload: reloadFeatured,
    } = useCatalogQuery(() => fetchProducts({ featured: true }), []);

    const {
        data: products,
        loading: productsLoading,
        error: productsError,
        reload: reloadProducts,
    } = useCatalogQuery(() => fetchProducts(), []);

    const loading = categoriesLoading || featuredLoading || productsLoading;
    const error = categoriesError || featuredError || productsError;

    if (loading) {
        return (
            <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <LoadingState label="Loading about page…" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <ErrorState
                    message={error.message}
                    onRetry={() => {
                        reloadCategories();
                        reloadFeatured();
                        reloadProducts();
                    }}
                />
            </div>
        );
    }

    const categoryList = categories ?? [];
    const productList = products ?? [];
    const featuredList = featured ?? [];
    const pillars = resolveDessertPillars(categoryList, productList);
    const shakes = pillars.find((pillar) => pillar.key === 'shakes');
    const iceCream = pillars.find((pillar) => pillar.key === 'ice-cream');
    const faluda = pillars.find((pillar) => pillar.key === 'faluda');
    const specialties = resolveAboutSpecialties(categoryList, productList);

    const heroImage = pickImage(
        featuredList[0],
        shakes?.products?.find((product) => product.image?.url),
        iceCream?.products?.find((product) => product.image?.url),
        productList.find((product) => product.image?.url),
    );

    const storyImage = pickImage(
        iceCream?.products?.find((product) => product.image?.url && product.image.url !== heroImage.url),
        shakes?.products?.find((product) => product.image?.url),
        faluda?.products?.find((product) => product.image?.url),
        featuredList[1],
    );

    const ambienceImage = pickImage(
        shakes?.products?.find((product) => product.image?.url),
        faluda?.products?.find((product) => product.image?.url),
        featuredList[0],
    );

    const ambienceSecondary = pickImage(
        iceCream?.products?.find((product) => product.image?.url),
        productList.find((product) => product.image?.url && product.image.url !== ambienceImage.url),
    );

    return (
        <div>
            <AboutHero imageUrl={heroImage.url} imageAlt={heroImage.alt} />
            <AboutStory imageUrl={storyImage.url} imageAlt={storyImage.alt} />
            <AboutPhilosophy />
            <AboutSpecialties specialties={specialties} />
            <AboutAmbience
                imageUrl={ambienceImage.url}
                imageAlt={ambienceImage.alt}
                secondaryImageUrl={ambienceSecondary.url}
                secondaryImageAlt={ambienceSecondary.alt}
            />
            <AboutTeam />
            <AboutValues />
            <AboutBrandStatement />
            <AboutCta />
        </div>
    );
}
