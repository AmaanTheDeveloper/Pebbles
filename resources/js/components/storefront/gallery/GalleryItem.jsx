function ViewIcon() {
    return (
        <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M2.5 12s3.5-6.5 9.5-6.5S21.5 12 21.5 12s-3.5 6.5-9.5 6.5S2.5 12 2.5 12Z" />
            <circle cx="12" cy="12" r="2.5" />
        </svg>
    );
}

export default function GalleryItem({ item, layoutClass = '', animationDelay = 0, onOpen }) {
    const roundedClass = layoutClass.includes('featured') ? 'rounded-3xl' : 'rounded-2xl';

    return (
        <button
            type="button"
            className={[
                'gallery-page-tile group block w-full overflow-hidden text-left shadow-md shadow-brand-green/10 ring-1 ring-brand-green/10 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-green/15',
                roundedClass,
                layoutClass,
            ]
                .filter(Boolean)
                .join(' ')}
            style={{ animationDelay: `${animationDelay}ms` }}
            onClick={() => onOpen(item)}
        >
            <div className={`gallery-tile relative ${item.image ? '' : 'min-h-48'}`}>
                {item.image ? (
                    <img
                        src={item.image}
                        alt={item.alt || item.title}
                        className={[
                            'size-full object-cover',
                            layoutClass.includes('featured') ? 'min-h-56 md:min-h-[28rem]' : 'aspect-square md:aspect-auto md:min-h-48',
                        ].join(' ')}
                        loading="lazy"
                        decoding="async"
                        width={600}
                        height={600}
                    />
                ) : (
                    <div
                        className={`flex min-h-48 flex-col items-center justify-center bg-gradient-to-br p-6 text-center text-white ${item.gradient ?? 'from-brand-green to-brand-green-light'}`}
                    >
                        <span className="font-display text-2xl font-semibold">{item.title}</span>
                        <p className="mt-2 max-w-xs text-sm text-white/85">{item.description}</p>
                    </div>
                )}

                <div className="gallery-overlay">
                    <span className="gallery-view">
                        <ViewIcon />
                    </span>
                    <div className="px-4 text-center">
                        <p className="font-display text-lg font-semibold text-white drop-shadow-sm">{item.title}</p>
                        <p className="mt-1 text-xs font-semibold tracking-wide text-brand-orange-soft uppercase">
                            {item.category}
                        </p>
                    </div>
                </div>
            </div>
        </button>
    );
}
