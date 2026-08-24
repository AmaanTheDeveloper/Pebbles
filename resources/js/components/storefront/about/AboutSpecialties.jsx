import { Link } from 'react-router-dom';
import { ScrollReveal, ScrollRevealItem } from '../../../hooks/useInView';

export default function AboutSpecialties({ specialties = [] }) {
    const items = specialties.filter((item) => item.available !== false);

    if (!items.length) {
        return null;
    }

    return (
        <section className="hp-section hp-section-divider bg-white/70 py-16 sm:py-20" id="specialties">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <ScrollReveal>
                    <div className="hp-section-head mx-auto mb-10 max-w-2xl text-center">
                        <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">
                            Our specialties
                        </p>
                        <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                            What Pebbles is known for
                        </h2>
                        <p className="mt-3 text-brand-muted">
                            Rich shakes, smooth ice cream, and refreshing drinks — explore the categories that define us.
                        </p>
                    </div>
                </ScrollReveal>

                <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {items.map((item, index) => (
                        <ScrollRevealItem key={item.key} index={index + 1} variant="scale" className="h-full">
                            <Link
                                to={item.href}
                                className={[
                                    'category-card group h-full',
                                    item.accent === 'green' ? 'accent-green' : '',
                                ]
                                    .filter(Boolean)
                                    .join(' ')}
                            >
                                <div className="category-card-media">
                                    {item.coverImage ? (
                                        <img src={item.coverImage} alt={item.coverAlt} loading="lazy" />
                                    ) : (
                                        <div className="flex size-full items-center justify-center bg-gradient-to-br from-brand-green/15 to-brand-orange/15 font-display text-2xl font-semibold text-brand-green">
                                            {item.label}
                                        </div>
                                    )}
                                    <div className="category-card-overlay" />
                                </div>
                                <div className="relative z-[1] space-y-2 p-5">
                                    <h3 className="font-display text-xl font-semibold text-brand-ink transition duration-300 group-hover:text-brand-orange">
                                        {item.label}
                                    </h3>
                                    <p className="text-sm leading-relaxed text-brand-muted">{item.blurb}</p>
                                    <span className="category-card-cta">
                                        Explore <span className="btn-arrow" aria-hidden="true">→</span>
                                    </span>
                                </div>
                            </Link>
                        </ScrollRevealItem>
                    ))}
                </div>
            </div>
        </section>
    );
}
