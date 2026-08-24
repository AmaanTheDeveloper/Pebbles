import { Link } from 'react-router-dom';
import { ScrollReveal, ScrollRevealItem } from '../../../hooks/useInView';

export default function HomeOffers({ offers = [], featuredProduct, content = {} }) {
    const activeOffers = (offers ?? []).slice(0, content.offer_limit ?? 3);

    if (activeOffers.length) {
        return (
            <section className="hp-section hp-section-divider mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="offers">
                <ScrollReveal>
                    <div className="hp-section-head mb-8 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">
                                {content.eyebrow ?? 'Offers'}
                            </p>
                            <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                                {content.title ?? 'Sweet deals, ready now'}
                            </h2>
                        </div>
                        <Link to="/offers" className="link-arrow text-sm">
                            {content.cta_label ?? 'All offers'}{' '}
                            <span className="btn-arrow" aria-hidden="true">→</span>
                        </Link>
                    </div>
                </ScrollReveal>

                <div className="grid gap-5 md:grid-cols-3">
                    {activeOffers.map((offer, index) => (
                        <ScrollRevealItem key={offer.id} index={index + 1} variant="scale" className="h-full">
                            <article className="offer-card group flex h-full flex-col overflow-hidden rounded-3xl bg-white ring-1 ring-brand-green/10">
                                {offer.image?.url ? (
                                    <div className="aspect-[16/10] overflow-hidden">
                                        <img
                                            src={offer.image.url}
                                            alt=""
                                            className="size-full object-cover transition duration-500 group-hover:scale-105"
                                            loading="lazy"
                                        />
                                    </div>
                                ) : (
                                    <div className="flex aspect-[16/10] items-center justify-center bg-gradient-to-br from-brand-green to-brand-green-light text-white">
                                        <p className="font-display text-xl font-semibold">{offer.type_label}</p>
                                    </div>
                                )}
                                <div className="flex flex-1 flex-col p-5">
                                    <div className="flex flex-wrap gap-2">
                                        <span className="rounded-full bg-brand-orange/10 px-2.5 py-0.5 text-xs font-semibold text-brand-orange">
                                            {offer.type_label}
                                        </span>
                                        {offer.offer_value ? (
                                            <span className="rounded-full bg-brand-green/10 px-2.5 py-0.5 text-xs font-semibold text-brand-green">
                                                {offer.offer_value}
                                            </span>
                                        ) : null}
                                    </div>
                                    <h3 className="mt-3 font-display text-xl font-semibold text-brand-ink transition group-hover:text-brand-orange">
                                        {offer.title}
                                    </h3>
                                    {offer.short_description ? (
                                        <p className="mt-2 text-sm text-brand-muted">{offer.short_description}</p>
                                    ) : null}
                                    <Link to="/offers" className="btn-orange btn-shine mt-auto inline-flex px-4 py-2 text-sm">
                                        View offer
                                        <span className="btn-arrow" aria-hidden="true">
                                            →
                                        </span>
                                    </Link>
                                </div>
                            </article>
                        </ScrollRevealItem>
                    ))}
                </div>
            </section>
        );
    }

    return (
        <section className="hp-section mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="offers">
            <ScrollReveal>
                <div className="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-brand-green via-brand-green to-brand-green-light p-8 text-white shadow-2xl shadow-brand-green/25 sm:p-12">
                    <div className="hp-float pointer-events-none absolute -top-10 -right-8 size-40 rounded-full bg-brand-orange/30 blur-2xl" />
                    <div className="pointer-events-none absolute bottom-0 left-10 size-48 rounded-full bg-white/10 blur-2xl" />

                    <div className="relative grid items-center gap-8 lg:grid-cols-2">
                        <div className="space-y-4">
                            <span className="inline-flex rounded-full bg-gradient-to-r from-brand-orange to-[#ea580c] px-3 py-1 text-xs font-semibold tracking-wide uppercase shadow-md shadow-brand-orange/40">
                                {content.fallback_badge ?? 'Treat yourself'}
                            </span>
                            <h2 className="font-display text-3xl font-semibold sm:text-4xl">
                                {content.fallback_title ?? 'Cool down with ice cream, shakes, faluda & kulfi'}
                            </h2>
                            <p className="max-w-md text-sm leading-relaxed text-white/85 sm:text-base">
                                {featuredProduct
                                    ? `${featuredProduct.name} is waiting — open it, customize, and add to cart.`
                                    : 'Browse the dessert menu and order your next favorite treat online.'}
                            </p>
                            <Link
                                to={featuredProduct ? `/product/${featuredProduct.slug}` : '/menu'}
                                className="btn-on-dark btn-shine px-5 py-3 text-sm"
                            >
                                {featuredProduct ? 'Order this treat' : content.fallback_cta_text ?? 'Explore the menu'}
                                <span className="btn-arrow" aria-hidden="true">
                                    →
                                </span>
                            </Link>
                        </div>

                        <div className="group relative mx-auto w-full max-w-sm overflow-hidden rounded-3xl bg-white/10 ring-1 ring-white/20 backdrop-blur-sm transition duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/20">
                            {featuredProduct?.image?.url ? (
                                <img
                                    src={featuredProduct.image.url}
                                    alt={featuredProduct.image.alt_text || featuredProduct.name}
                                    className="aspect-square w-full object-cover transition duration-500 group-hover:scale-105"
                                    loading="lazy"
                                />
                            ) : (
                                <div className="flex aspect-square items-center justify-center p-6 text-center text-white/90">
                                    <p className="font-display text-2xl font-semibold">Something sweet awaits</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </ScrollReveal>
        </section>
    );
}
