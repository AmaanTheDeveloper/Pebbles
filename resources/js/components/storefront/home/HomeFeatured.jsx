import { Link } from 'react-router-dom';
import { ScrollReveal } from '../../../hooks/useInView';
import EmptyState from '../EmptyState';
import ProductCard from '../ProductCard';

export default function HomeFeatured({ products }) {
    return (
        <section className="hp-section relative bg-white/70 py-16 sm:py-20" id="featured">
            <div className="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-brand-orange/30 to-transparent" />
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <ScrollReveal>
                    <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-xs font-semibold tracking-widest text-brand-green uppercase">Featured</p>
                            <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                                Pebbles favorites
                            </h2>
                        </div>
                        <Link to="/menu" className="link-arrow text-sm">
                            See full menu <span className="btn-arrow" aria-hidden="true">→</span>
                        </Link>
                    </div>
                </ScrollReveal>

                {!products?.length ? (
                    <EmptyState
                        title="No featured treats yet"
                        description="Check the full menu for shakes, ice cream, and more."
                    />
                ) : (
                    <ScrollReveal delayClass="hp-scroll-delay-1">
                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {products.map((product) => (
                                <ProductCard key={product.id} product={product} featured />
                            ))}
                        </div>
                    </ScrollReveal>
                )}
            </div>
        </section>
    );
}
