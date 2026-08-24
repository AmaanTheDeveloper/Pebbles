import { useCallback, useEffect } from 'react';
import { createPortal } from 'react-dom';

function CloseIcon() {
    return (
        <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path d="M6 6l12 12M18 6 6 18" strokeLinecap="round" />
        </svg>
    );
}

function ChevronIcon({ direction = 'left' }) {
    return (
        <svg
            viewBox="0 0 24 24"
            className={`size-5 ${direction === 'right' ? 'rotate-180' : ''}`}
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            aria-hidden="true"
        >
            <path d="M14 6l-6 6 6 6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

export default function GalleryLightbox({ items, activeIndex, onClose, onNavigate }) {
    const item = activeIndex === null ? null : (items[activeIndex] ?? null);
    const hasPrevious = activeIndex > 0;
    const hasNext = activeIndex < items.length - 1;

    const handlePrevious = useCallback(() => {
        if (hasPrevious) {
            onNavigate(activeIndex - 1);
        }
    }, [activeIndex, hasPrevious, onNavigate]);

    const handleNext = useCallback(() => {
        if (hasNext) {
            onNavigate(activeIndex + 1);
        }
    }, [activeIndex, hasNext, onNavigate]);

    useEffect(() => {
        if (item === null) {
            return undefined;
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                onClose();
            }

            if (event.key === 'ArrowLeft') {
                handlePrevious();
            }

            if (event.key === 'ArrowRight') {
                handleNext();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [item, onClose, handlePrevious, handleNext]);

    if (item === null || typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <div className="gallery-lightbox" role="dialog" aria-modal="true" aria-label={item.title}>
            <button type="button" className="gallery-lightbox-backdrop" aria-label="Close gallery viewer" onClick={onClose} />

            <div className="gallery-lightbox-panel">
                <button type="button" className="gallery-lightbox-close" aria-label="Close" onClick={onClose}>
                    <CloseIcon />
                </button>

                {hasPrevious ? (
                    <button
                        type="button"
                        className="gallery-lightbox-nav gallery-lightbox-nav-prev"
                        aria-label="Previous image"
                        onClick={handlePrevious}
                    >
                        <ChevronIcon direction="left" />
                    </button>
                ) : null}

                <figure className="gallery-lightbox-figure">
                    {item.image ? (
                        <img src={item.image} alt={item.alt || item.title} className="gallery-lightbox-image" />
                    ) : (
                        <div
                            className={`gallery-lightbox-placeholder flex min-h-64 flex-col items-center justify-center bg-gradient-to-br p-10 text-center text-white ${item.gradient ?? 'from-brand-green to-brand-green-light'}`}
                        >
                            <span className="font-display text-3xl font-semibold">{item.title}</span>
                        </div>
                    )}
                    <figcaption className="gallery-lightbox-caption">
                        <p className="font-display text-xl font-semibold text-white">{item.title}</p>
                        <p className="mt-1 text-xs font-semibold tracking-wide text-brand-orange-soft uppercase">
                            {item.category}
                        </p>
                        {item.description ? (
                            <p className="mt-2 text-sm leading-relaxed text-white/80">{item.description}</p>
                        ) : null}
                    </figcaption>
                </figure>

                {hasNext ? (
                    <button
                        type="button"
                        className="gallery-lightbox-nav gallery-lightbox-nav-next"
                        aria-label="Next image"
                        onClick={handleNext}
                    >
                        <ChevronIcon direction="right" />
                    </button>
                ) : null}
            </div>
        </div>,
        document.body,
    );
}
