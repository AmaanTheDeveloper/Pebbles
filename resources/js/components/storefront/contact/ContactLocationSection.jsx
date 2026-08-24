import OpenStatusBadge from './OpenStatusBadge';

export default function ContactLocationSection({ location }) {
    if (!location) {
        return null;
    }

    return (
        <section className="hp-section mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
            <div className="hp-section-head mx-auto max-w-2xl text-center">
                <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">Visit Pebbles</p>
                <h2 className="mt-3 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">{location.name}</h2>
                <div className="mt-4 flex justify-center">
                    <OpenStatusBadge isOpen={location.is_open_now} />
                </div>
            </div>

            <div className="mt-10 grid gap-8 lg:grid-cols-2">
                <div className="space-y-6">
                    {location.image_url ? (
                        <img
                            src={location.image_url}
                            alt={`${location.name} branch`}
                            className="h-56 w-full rounded-2xl object-cover shadow-lg ring-1 ring-brand-green/10"
                            loading="lazy"
                        />
                    ) : null}

                    <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-green/10 sm:p-6">
                        <ul className="space-y-3 text-sm text-brand-muted">
                            {location.address ? (
                                <li>
                                    <span className="font-medium text-brand-ink">Address:</span> {location.address}
                                </li>
                            ) : null}
                            {location.phone ? (
                                <li>
                                    <span className="font-medium text-brand-ink">Phone:</span>{' '}
                                    <a href={`tel:${location.phone}`} className="hover:text-brand-orange">
                                        {location.phone}
                                    </a>
                                </li>
                            ) : null}
                        </ul>

                        <div className="mt-5 flex flex-wrap gap-3">
                            {location.directions_url ? (
                                <a
                                    href={location.directions_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="btn-orange btn-shine px-4 py-2 text-sm"
                                >
                                    Get Directions
                                </a>
                            ) : null}
                            {location.phone ? (
                                <a href={`tel:${location.phone}`} className="contact-secondary-btn">
                                    Call
                                </a>
                            ) : null}
                            {location.whatsapp_url ? (
                                <a
                                    href={location.whatsapp_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="contact-secondary-btn"
                                >
                                    WhatsApp
                                </a>
                            ) : null}
                        </div>
                    </div>

                    {location.opening_hours_schedule?.length ? (
                        <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-green/10 sm:p-6">
                            <h3 className="font-display text-lg font-semibold text-brand-ink">Branch Hours</h3>
                            <ul className="mt-3 space-y-2">
                                {location.opening_hours_schedule.map((day) => (
                                    <li key={day.day} className="flex justify-between gap-4 text-sm">
                                        <span className="font-medium text-brand-ink">{day.label}</span>
                                        <span className="text-brand-muted">{day.formatted}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                </div>

                {location.map_embed_url ? (
                    <div className="overflow-hidden rounded-2xl shadow-lg ring-1 ring-brand-green/10">
                        <iframe
                            title={`Map for ${location.name}`}
                            src={location.map_embed_url}
                            className="contact-map-frame"
                            loading="lazy"
                            referrerPolicy="no-referrer-when-downgrade"
                        />
                    </div>
                ) : null}
            </div>
        </section>
    );
}

export function ContactMapSection({ contact, title = 'Find us on the map' }) {
    const embedUrl = contact?.map_embed_url;

    if (!embedUrl) {
        return null;
    }

    return (
        <section className="hp-section mx-auto max-w-6xl px-4 pb-16 sm:px-6 sm:pb-20">
            <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">Map</p>
                    <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink">{title}</h2>
                </div>
                {contact.directions_url ? (
                    <a
                        href={contact.directions_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="btn-orange btn-shine px-4 py-2 text-sm"
                    >
                        Get Directions
                    </a>
                ) : null}
            </div>
            <div className="overflow-hidden rounded-2xl shadow-lg ring-1 ring-brand-green/10">
                <iframe
                    title="Pebbles location map"
                    src={embedUrl}
                    className="contact-map-frame"
                    loading="lazy"
                    referrerPolicy="no-referrer-when-downgrade"
                />
            </div>
        </section>
    );
}
