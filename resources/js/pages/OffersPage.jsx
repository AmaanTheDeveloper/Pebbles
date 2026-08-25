import { Link } from 'react-router-dom';
import { fetchOffers } from '../api/catalog';
import EmptyState from '../components/storefront/EmptyState';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import SeoHead from '../components/seo/SeoHead';
import { useCatalogQuery } from '../hooks/useCatalog';

const TYPE_SECTIONS = [
    { key: 'combo_offers', label: 'Combo Offers' },
    { key: 'discounts', label: 'Discounts' },
    { key: 'special_events', label: 'Special Events' },
    { key: 'seasonal_deals', label: 'Seasonal Deals' },
];

function formatValidity(offer) {
    if (!offer.starts_at && !offer.ends_at) {
        return null;
    }

    const start = offer.starts_at ? new Date(offer.starts_at).toLocaleDateString() : null;
    const end = offer.ends_at ? new Date(offer.ends_at).toLocaleDateString() : null;

    if (start && end) {
        return `Valid ${start} – ${end}`;
    }

    if (end) {
        return `Until ${end}`;
    }

    return `From ${start}`;
}

function OfferCta({ offer }) {
    if (!offer.cta_url) {
        return null;
    }

    const label = offer.cta_label || 'View offer';
    const className = 'btn-orange btn-shine mt-4 inline-flex px-4 py-2 text-sm';
    const isExternal = /^https?:\/\//i.test(offer.cta_url);

    if (isExternal) {
        return (
            <a href={offer.cta_url} className={className} target="_blank" rel="noreferrer">
                {label}
                <span className="btn-arrow" aria-hidden="true">
                    →
                </span>
            </a>
        );
    }

    return (
        <Link to={offer.cta_url} className={className}>
            {label}
            <span className="btn-arrow" aria-hidden="true">
                →
            </span>
        </Link>
    );
}

function OfferCard({ offer }) {
    const validity = formatValidity(offer);

    return (
        <article className="flex h-full flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-brand-green/10 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-brand-green/10 hover:ring-brand-orange/30">
            {offer.image?.url ? (
                <img
                    src={offer.image.url}
                    alt=""
                    className="aspect-[16/10] w-full object-cover"
                    loading="lazy"
                    decoding="async"
                />
            ) : (
                <div className="flex aspect-[16/10] items-center justify-center bg-gradient-to-br from-brand-green to-brand-green-light text-white">
                    <p className="font-display text-xl font-semibold">{offer.type_label}</p>
                </div>
            )}

            <div className="flex flex-1 flex-col p-5">
                <div className="flex flex-wrap items-center gap-2">
                    <span className="rounded-full bg-brand-orange/10 px-2.5 py-0.5 text-xs font-semibold text-brand-orange">
                        {offer.type_label}
                    </span>
                    {offer.offer_value ? (
                        <span className="rounded-full bg-brand-green/10 px-2.5 py-0.5 text-xs font-semibold text-brand-green">
                            {offer.offer_value}
                        </span>
                    ) : null}
                </div>

                <h3 className="mt-3 font-display text-xl font-semibold text-brand-ink">{offer.title}</h3>

                {offer.short_description ? (
                    <p className="mt-2 text-sm leading-relaxed text-brand-muted">{offer.short_description}</p>
                ) : null}

                {offer.description ? (
                    <p className="mt-2 text-sm leading-relaxed text-stone-600">{offer.description}</p>
                ) : null}

                {validity ? <p className="mt-3 text-xs font-medium text-stone-500">{validity}</p> : null}

                <div className="mt-auto">
                    <OfferCta offer={offer} />
                </div>
            </div>
        </article>
    );
}

export default function OffersPage() {
    const { data: offers, loading, error, reload } = useCatalogQuery(fetchOffers, []);

    if (loading) {
        return <LoadingState label="Loading offers…" />;
    }

    if (error) {
        return <ErrorState message={error.message} onRetry={reload} />;
    }

    const list = offers ?? [];
    const sections = TYPE_SECTIONS.map((section) => ({
        ...section,
        offers: list.filter((offer) => offer.type === section.key),
    })).filter((section) => section.offers.length > 0);

    return (
        <div className="space-y-8 py-2 sm:py-4">
            <SeoHead
                title="Offers"
                description="Combos, discounts, and seasonal deals on shakes, ice cream, and more — current offers from Pebbles."
                path="/offers"
            />
            <div className="space-y-2">
                <p className="text-xs font-semibold tracking-widest text-brand-orange uppercase">Offers</p>
                <h1 className="font-display text-3xl font-semibold tracking-tight text-brand-ink sm:text-4xl">
                    Current offers
                </h1>
                <p className="max-w-2xl text-sm text-brand-muted sm:text-base">
                    Combos, discounts, and seasonal deals on shakes, ice cream, Limca, and more — updated as they go
                    live.
                </p>
            </div>

            {sections.length === 0 ? (
                <EmptyState
                    title="No active offers right now"
                    description="Check back soon, or browse shakes, ice cream, and more on the menu."
                />
            ) : (
                sections.map((section) => (
                    <section key={section.key} className="space-y-4">
                        <h2 className="font-display text-2xl font-semibold text-brand-ink">{section.label}</h2>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {section.offers.map((offer) => (
                                <OfferCard key={offer.id} offer={offer} />
                            ))}
                        </div>
                    </section>
                ))
            )}
        </div>
    );
}
