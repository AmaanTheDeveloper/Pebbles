import { ScrollReveal } from '../../../hooks/useInView';

export default function HomeLocation({ branch, content = {} }) {
    if (!branch) {
        return (
            <section className="hp-section bg-brand-cream py-16 sm:py-20" id="location">
                <ScrollReveal>
                    <div className="mx-auto max-w-6xl px-4 text-center sm:px-6">
                        <p className="text-xs font-semibold tracking-widest text-brand-orange uppercase">
                            {content.empty_eyebrow ?? 'Location'}
                        </p>
                        <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink">
                            {content.empty_title ?? 'Find a branch'}
                        </h2>
                        <p className="mt-3 text-brand-muted">
                            {content.empty_description ??
                                'Choose a branch from the header to see address, hours, and contact details.'}
                        </p>
                    </div>
                </ScrollReveal>
            </section>
        );
    }

    const mapsQuery = encodeURIComponent([branch.name, branch.address].filter(Boolean).join(', '));
    const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${mapsQuery}`;

    return (
        <section className="hp-section bg-brand-cream py-16 sm:py-20" id="location">
            <div className="mx-auto grid max-w-6xl gap-8 px-4 sm:px-6 lg:grid-cols-2 lg:items-stretch">
                <ScrollReveal>
                    <div className="location-card group rounded-3xl bg-white p-8 shadow-sm ring-1 ring-brand-green/10">
                        <div className="mb-4 flex size-11 items-center justify-center rounded-2xl bg-brand-green/10 text-brand-green transition duration-300 group-hover:-translate-y-0.5 group-hover:bg-brand-orange/15 group-hover:text-brand-orange">
                            <svg viewBox="0 0 24 24" className="size-5" fill="currentColor" aria-hidden="true">
                                <path d="M12 2.25c-3.7 0-6.75 2.9-6.75 6.55 0 4.55 6.05 11.7 6.3 12l.45.5.45-.5c.25-.3 6.3-7.45 6.3-12 0-3.65-3.05-6.55-6.75-6.55Zm0 9a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5Z" />
                            </svg>
                        </div>
                        <p className="text-xs font-semibold tracking-widest text-brand-orange uppercase">
                            {content.card_eyebrow ?? 'Location'}
                        </p>
                        <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink">{branch.name}</h2>
                        <ul className="mt-6 space-y-3 text-sm text-brand-muted">
                            {branch.address ? <li className="text-brand-ink">{branch.address}</li> : null}
                            {branch.phone ? (
                                <li>
                                    <a
                                        className="font-semibold text-brand-green transition hover:text-brand-orange"
                                        href={`tel:${branch.phone}`}
                                    >
                                        {branch.phone}
                                    </a>
                                </li>
                            ) : null}
                            {branch.opens_at && branch.closes_at ? (
                                <li>
                                    Hours: {String(branch.opens_at).slice(0, 5)} – {String(branch.closes_at).slice(0, 5)}
                                </li>
                            ) : null}
                        </ul>
                        <a href={mapsUrl} target="_blank" rel="noreferrer" className="btn-green mt-8 px-5 py-3 text-sm">
                            {content.directions_cta_text ?? 'Get directions'}
                            <span className="btn-arrow" aria-hidden="true">
                                →
                            </span>
                        </a>
                    </div>
                </ScrollReveal>

                <ScrollReveal delayClass="hp-scroll-delay-1">
                    <div className="location-card overflow-hidden rounded-3xl bg-gradient-to-br from-brand-green to-brand-green-light text-white shadow-lg ring-1 ring-brand-green/20">
                        <div className="flex h-full min-h-64 flex-col justify-between bg-[radial-gradient(circle_at_20%_20%,rgb(255_255_255/0.15),transparent_45%)] p-8">
                            <div>
                                <p className="text-xs font-semibold tracking-widest text-brand-orange-soft uppercase">
                                    {content.visit_eyebrow ?? 'Visit us'}
                                </p>
                                <p className="mt-3 font-display text-2xl font-semibold">
                                    {content.visit_title ?? 'Come for dessert. Stay for more.'}
                                </p>
                                <p className="mt-3 max-w-sm text-sm text-white/80">
                                    {content.visit_description ??
                                        'Ice cream, shakes, faluda, and kulfi at your nearest branch — switch locations anytime in the header.'}
                                </p>
                            </div>
                            <p className="text-sm font-medium text-white/90">{branch.address || branch.name}</p>
                        </div>
                    </div>
                </ScrollReveal>
            </div>
        </section>
    );
}
