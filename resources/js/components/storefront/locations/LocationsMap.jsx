import { useEffect, useState } from 'react';

export default function LocationsMap({ locations, selectedId, onSelect }) {
    const selected = locations.find((location) => location.id === selectedId) ?? locations[0] ?? null;

    useEffect(() => {
        if (locations.length && !locations.some((location) => location.id === selectedId)) {
            onSelect?.(locations[0].id);
        }
    }, [locations, selectedId, onSelect]);

    if (!locations.length || !selected?.map_embed_url) {
        return null;
    }

    return (
        <section id="locations-map" className="hp-section mx-auto max-w-6xl px-4 pb-16 sm:px-6 sm:pb-20">
            <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">Map</p>
                    <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink">{selected.name}</h2>
                </div>
                <div className="flex flex-wrap gap-2">
                    {locations.map((location) => (
                        <button
                            key={location.id}
                            type="button"
                            onClick={() => onSelect?.(location.id)}
                            className={[
                                'rounded-full px-3 py-1.5 text-xs font-semibold transition',
                                selected.id === location.id
                                    ? 'bg-brand-orange text-white shadow-md shadow-brand-orange/30'
                                    : 'bg-white text-brand-ink ring-1 ring-brand-green/15 hover:ring-brand-orange/40',
                            ].join(' ')}
                        >
                            {location.name}
                        </button>
                    ))}
                </div>
            </div>

            <div className="overflow-hidden rounded-2xl shadow-lg ring-1 ring-brand-green/10">
                <iframe
                    key={selected.id}
                    title={`Map for ${selected.name}`}
                    src={selected.map_embed_url}
                    className="contact-map-frame"
                    loading="lazy"
                    referrerPolicy="no-referrer-when-downgrade"
                />
            </div>
        </section>
    );
}
