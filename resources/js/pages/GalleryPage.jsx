import { useEffect, useMemo, useState } from 'react';
import { fetchGalleryItems } from '../api/gallery';
import GalleryCta from '../components/storefront/gallery/GalleryCta';
import GalleryFilters from '../components/storefront/gallery/GalleryFilters';
import GalleryGrid from '../components/storefront/gallery/GalleryGrid';
import GalleryHero from '../components/storefront/gallery/GalleryHero';
import GalleryLightbox from '../components/storefront/gallery/GalleryLightbox';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import { ScrollReveal } from '../hooks/useInView';
import { useCatalogQuery } from '../hooks/useCatalog';
import { filterGalleryItems } from '../lib/galleryContent';

export default function GalleryPage() {
    const [activeCategory, setActiveCategory] = useState('All');
    const [isFiltering, setIsFiltering] = useState(false);
    const [lightboxIndex, setLightboxIndex] = useState(null);

    const { data: galleryItems, loading, error, reload } = useCatalogQuery(fetchGalleryItems, []);

    const allItems = galleryItems ?? [];

    const filteredItems = useMemo(
        () => filterGalleryItems(allItems, activeCategory),
        [allItems, activeCategory],
    );

    const handleCategoryChange = (category) => {
        if (category === activeCategory) {
            return;
        }

        setIsFiltering(true);
        setLightboxIndex(null);
        window.setTimeout(() => {
            setActiveCategory(category);
            setIsFiltering(false);
        }, 180);
    };

    useEffect(() => {
        setLightboxIndex(null);
    }, [activeCategory]);

    const openLightbox = (item) => {
        const index = filteredItems.findIndex((candidate) => candidate.id === item.id);
        setLightboxIndex(index >= 0 ? index : null);
    };

    if (loading) {
        return (
            <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <LoadingState label="Loading gallery…" />
            </div>
        );
    }

    if (error) {
        return (
            <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <ErrorState message={error.message} onRetry={reload} />
            </div>
        );
    }

    return (
        <div>
            <GalleryHero />

            <section className="hp-section mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16" id="gallery-grid">
                <ScrollReveal>
                    <div className="hp-section-head mx-auto mb-8 max-w-2xl text-center">
                        <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">
                            Browse by category
                        </p>
                        <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                            Moments worth savoring
                        </h2>
                        <p className="mt-3 text-brand-muted">
                            Filter food, interior, events, and atmosphere — then tap any image to view it larger.
                        </p>
                    </div>
                </ScrollReveal>

                <ScrollReveal delayClass="hp-scroll-delay-1">
                    <GalleryFilters activeCategory={activeCategory} onChange={handleCategoryChange} />
                </ScrollReveal>

                <div className="mt-8">
                    <GalleryGrid
                        items={filteredItems}
                        filterKey={activeCategory}
                        isFiltering={isFiltering}
                        onOpenItem={openLightbox}
                    />
                </div>
            </section>

            <GalleryCta />

            <GalleryLightbox
                items={filteredItems}
                activeIndex={lightboxIndex}
                onClose={() => setLightboxIndex(null)}
                onNavigate={setLightboxIndex}
            />
        </div>
    );
}
