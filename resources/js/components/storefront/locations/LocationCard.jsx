import OpenStatusBadge from '../contact/OpenStatusBadge';

export default function LocationCard({ location, active = false, onSelect }) {
    return (
        <article
            className={[
                'location-card lift-card overflow-hidden rounded-2xl bg-white ring-1 transition duration-300',
                active ? 'ring-brand-orange shadow-lg shadow-brand-orange/10' : 'ring-brand-green/10',
            ].join(' ')}
        >
            {location.image_url ? (
                <button type="button" onClick={() => onSelect?.(location)} className="location-card-image-wrap block w-full">
                    <img
                        src={location.image_url}
                        alt={`${location.name} branch`}
                        className="location-card-image h-44 w-full object-cover"
                        loading="lazy"
                        decoding="async"
                        width={640}
                        height={176}
                    />
                </button>
            ) : null}

            <div className="space-y-4 p-5 sm:p-6">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 className="font-display text-xl font-semibold text-brand-ink">{location.name}</h3>
                        {location.address ? <p className="mt-1 text-sm text-brand-muted">{location.address}</p> : null}
                    </div>
                    <OpenStatusBadge isOpen={location.is_open_now} />
                </div>

                {location.opening_hours_schedule?.length ? (
                    <ul className="space-y-1 text-sm text-brand-muted">
                        {location.opening_hours_schedule.slice(0, 3).map((day) => (
                            <li key={day.day} className="flex justify-between gap-3">
                                <span>{day.label}</span>
                                <span>{day.formatted}</span>
                            </li>
                        ))}
                        {location.opening_hours_schedule.length > 3 ? (
                            <li className="text-xs text-brand-muted">+ full weekly hours on map</li>
                        ) : null}
                    </ul>
                ) : null}

                <div className="flex flex-wrap gap-2">
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
                    {onSelect ? (
                        <button type="button" onClick={() => onSelect(location)} className="contact-secondary-btn">
                            View on map
                        </button>
                    ) : null}
                </div>
            </div>
        </article>
    );
}
