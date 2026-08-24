import { Link } from 'react-router-dom';
import { ScrollReveal, ScrollRevealItem } from '../../../hooks/useInView';

export default function HomeFavorites({ cards = [], content = {} }) {
    const showcase = cards.filter((card) => card?.available !== false);

    if (!showcase.length) {
        return null;
    }

    return (
        <section className="hp-section hp-section-divider mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="favorites">
            <ScrollReveal>
                <div className="hp-section-head mx-auto mb-10 max-w-2xl text-center">
                    <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">
                        {content.eyebrow ?? 'Explore our favorites'}
                    </p>
                    <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                        {content.title ?? 'Scoops, sips & signature treats'}
                    </h2>
                    <p className="mt-3 text-brand-muted">
                        {content.description ??
                            'Ice cream, shakes, faluda, and special picks — start here, then order online.'}
                    </p>
                </div>
            </ScrollReveal>

            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {showcase.map((card, index) => (
                    <ScrollRevealItem key={card.key} index={index + 1} variant="scale" className="h-full">
                        <Link
                            to={card.href}
                            className={[
                                'category-card group h-full',
                                `accent-${card.accent}`,
                                card.isSpecial ? 'category-card-special' : '',
                            ]
                                .filter(Boolean)
                                .join(' ')}
                        >
                            <div className="category-card-media">
                                {card.coverImage ? (
                                    <img src={card.coverImage} alt={card.coverAlt} loading="lazy" />
                                ) : (
                                    <div className="flex size-full items-center justify-center bg-gradient-to-br from-brand-green/20 to-brand-orange/20 font-display text-2xl font-semibold text-brand-green">
                                        {card.label}
                                    </div>
                                )}
                                <div className="category-card-overlay" />
                                {card.badge ? (
                                    <span className="category-card-badge">{card.badge}</span>
                                ) : null}
                            </div>
                            <div className="relative z-[1] space-y-2 p-5">
                                <h3 className="font-display text-xl font-semibold text-brand-ink transition duration-300 group-hover:text-brand-orange">
                                    {card.label}
                                </h3>
                                <p className="text-sm leading-relaxed text-brand-muted">{card.blurb}</p>
                                <span className="category-card-cta">
                                    Explore <span className="btn-arrow" aria-hidden="true">→</span>
                                </span>
                            </div>
                        </Link>
                    </ScrollRevealItem>
                ))}
            </div>
        </section>
    );
}
