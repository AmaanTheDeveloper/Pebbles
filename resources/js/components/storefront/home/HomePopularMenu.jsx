import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { ScrollReveal } from '../../../hooks/useInView';
import EmptyState from '../EmptyState';
import ProductCard from '../ProductCard';

export default function HomePopularMenu({ categories, products }) {
    const [activeSlug, setActiveSlug] = useState('all');

    const filtered = useMemo(() => {
        if (!products?.length) {
            return [];
        }
        if (activeSlug === 'all') {
            return products.slice(0, 8);
        }
        return products.filter((product) => product.category?.slug === activeSlug).slice(0, 8);
    }, [products, activeSlug]);

    return (
        <section className="hp-section mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="popular-menu">
            <ScrollReveal>
                <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-semibold tracking-widest text-brand-green uppercase">Popular treats</p>
                        <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                            Sip. Scoop. Enjoy.
                        </h2>
                    </div>
                    <Link to="/menu" className="link-arrow text-sm">
                        View full menu <span className="btn-arrow" aria-hidden="true">→</span>
                    </Link>
                </div>
            </ScrollReveal>

            <ScrollReveal delayClass="hp-scroll-delay-1">
                <div className="-mx-1 mb-8 flex gap-2 overflow-x-auto px-1 pb-1" role="tablist" aria-label="Menu categories">
                    <button
                        type="button"
                        role="tab"
                        aria-selected={activeSlug === 'all'}
                        onClick={() => setActiveSlug('all')}
                        className={['cat-chip', activeSlug === 'all' ? 'is-active' : ''].filter(Boolean).join(' ')}
                    >
                        All
                    </button>
                    {(categories ?? []).map((category) => (
                        <button
                            key={category.id}
                            type="button"
                            role="tab"
                            aria-selected={activeSlug === category.slug}
                            onClick={() => setActiveSlug(category.slug)}
                            className={['cat-chip', activeSlug === category.slug ? 'is-active' : ''].filter(Boolean).join(' ')}
                        >
                            {category.name}
                        </button>
                    ))}
                </div>
            </ScrollReveal>

            {!filtered.length ? (
                <EmptyState title="No items in this category" description="Try another category or open the full menu." />
            ) : (
                <div key={activeSlug} className="menu-grid-fade grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    {filtered.map((product) => (
                        <ProductCard key={product.id} product={product} />
                    ))}
                </div>
            )}
        </section>
    );
}
