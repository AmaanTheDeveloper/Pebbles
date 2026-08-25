import { useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchMenuBookPages } from '../api/menuBook';
import MenuBookHero from '../components/storefront/menuBook/MenuBookHero';
import MenuBookLightbox from '../components/storefront/menuBook/MenuBookLightbox';
import EmptyState from '../components/storefront/EmptyState';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import SeoHead from '../components/seo/SeoHead';
import { ScrollReveal, ScrollRevealItem } from '../hooks/useInView';
import { useCatalogQuery } from '../hooks/useCatalog';

export default function MenuBookPage() {
    const [lightboxIndex, setLightboxIndex] = useState(null);
    const { data: pages, loading, error, reload } = useCatalogQuery(fetchMenuBookPages, []);
    const menuPages = pages ?? [];

    const openLightbox = (index) => {
        setLightboxIndex(index);
    };

    if (loading) {
        return (
            <div>
                <SeoHead
                    title="Menu book"
                    description="Browse the Pebbles restaurant menu book — full pages of ice cream, shakes, and desserts."
                    path="/menu-book"
                />
                <MenuBookHero />
                <div className="mx-auto max-w-4xl px-4 py-16 sm:px-6">
                    <LoadingState label="Loading menu…" />
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div>
                <SeoHead
                    title="Menu book"
                    description="Browse the Pebbles restaurant menu book — full pages of ice cream, shakes, and desserts."
                    path="/menu-book"
                />
                <MenuBookHero />
                <div className="mx-auto max-w-4xl px-4 py-16 sm:px-6">
                    <ErrorState message={error.message} onRetry={reload} />
                </div>
            </div>
        );
    }

    return (
        <div>
            <SeoHead
                title="Menu book"
                description="Browse the Pebbles restaurant menu book — full pages of ice cream, shakes, and desserts."
                path="/menu-book"
            />
            <MenuBookHero />

            <section className="hp-section mx-auto max-w-4xl px-4 py-12 sm:px-6 sm:py-16">
                <ScrollReveal>
                    <div className="mb-10 flex flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
                        <p className="text-sm text-brand-muted">
                            Tap any page to view it fullscreen. Swipe through pages with the arrows or keyboard.
                        </p>
                        <Link to="/menu" className="btn-orange btn-shine shrink-0 px-6 py-3 text-sm">
                            Order Online
                        </Link>
                    </div>
                </ScrollReveal>

                {menuPages.length === 0 ? (
                    <EmptyState
                        title="Menu coming soon"
                        description="Our restaurant menu pages will appear here once they are published."
                    />
                ) : (
                    <div className="menu-book-stack space-y-8 sm:space-y-10">
                        {menuPages.map((page, index) => (
                            <ScrollRevealItem key={page.id} index={index}>
                                <article className="menu-book-page">
                                    {page.title ? (
                                        <p className="mb-3 text-center text-xs font-semibold tracking-wide text-brand-muted uppercase">
                                            {page.title}
                                        </p>
                                    ) : null}
                                    <button
                                        type="button"
                                        className="menu-book-page-trigger group w-full"
                                        aria-label={`View ${page.title || `menu page ${index + 1}`} fullscreen`}
                                        onClick={() => openLightbox(index)}
                                    >
                                        <img
                                            src={page.image}
                                            alt={page.alt || page.title || `Pebbles menu page ${index + 1}`}
                                            className="menu-book-page-image"
                                            loading={index === 0 ? 'eager' : 'lazy'}
                                            decoding="async"
                                        />
                                        <span className="menu-book-page-hint">Tap to enlarge</span>
                                    </button>
                                </article>
                            </ScrollRevealItem>
                        ))}
                    </div>
                )}

                {menuPages.length > 0 ? (
                    <ScrollReveal>
                        <div className="menu-book-footer-cta mt-14 text-center">
                            <p className="text-brand-muted">Ready to order?</p>
                            <Link to="/menu" className="btn-green mt-4 inline-flex px-6 py-3 text-sm">
                                Browse Digital Menu
                            </Link>
                        </div>
                    </ScrollReveal>
                ) : null}
            </section>

            <MenuBookLightbox
                pages={menuPages}
                activeIndex={lightboxIndex}
                onClose={() => setLightboxIndex(null)}
                onNavigate={setLightboxIndex}
            />
        </div>
    );
}
