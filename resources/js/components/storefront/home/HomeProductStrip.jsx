import { Link } from 'react-router-dom';
import { ScrollReveal, ScrollRevealItem } from '../../../hooks/useInView';
import EmptyState from '../EmptyState';
import ProductCard from '../ProductCard';

export default function HomeProductStrip({
    id,
    eyebrow,
    title,
    description,
    products = [],
    href,
    ctaLabel = 'View all',
    tone = 'white',
    content = {},
    productLimit = 8,
}) {
    const resolvedTone = content.tone ?? tone;
    const sectionClass =
        resolvedTone === 'cream'
            ? 'hp-section bg-brand-cream/80 py-16 sm:py-20'
            : resolvedTone === 'soft-green'
              ? 'hp-section bg-gradient-to-b from-white to-[#f3faf6] py-16 sm:py-20'
              : 'hp-section bg-white/70 py-16 sm:py-20';

    return (
        <section className={`${sectionClass} hp-section-divider`} id={content.section_id ?? id}>
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <ScrollReveal>
                    <div className="hp-section-head mb-8 flex flex-wrap items-end justify-between gap-4">
                        <div className="max-w-2xl">
                            <p className="hp-section-eyebrow text-xs font-semibold text-brand-green uppercase">
                                {content.eyebrow ?? eyebrow}
                            </p>
                            <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                                {content.title ?? title}
                            </h2>
                            {(content.description ?? description) ? (
                                <p className="mt-3 text-brand-muted">{content.description ?? description}</p>
                            ) : null}
                        </div>
                        {href ? (
                            <Link to={href} className="link-arrow text-sm">
                                {content.cta_label ?? ctaLabel}{' '}
                                <span className="btn-arrow" aria-hidden="true">
                                    →
                                </span>
                            </Link>
                        ) : null}
                    </div>
                </ScrollReveal>

                {!products.length ? (
                    <EmptyState
                        title={`No ${(content.title ?? title).toLowerCase()} yet`}
                        description="Check the full menu for today’s treats."
                    />
                ) : (
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {products.slice(0, content.product_limit ?? productLimit).map((product, index) => (
                            <ScrollRevealItem key={product.id} index={index + 1} variant="scale" className="h-full">
                                <ProductCard product={product} featured={Boolean(product.is_featured)} />
                            </ScrollRevealItem>
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
