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

export default function MenuBookLightbox({ pages, activeIndex, onClose, onNavigate }) {
    const page = activeIndex === null ? null : (pages[activeIndex] ?? null);
    const hasPrevious = activeIndex > 0;
    const hasNext = activeIndex < pages.length - 1;
    const label = page?.title || `Menu page ${(activeIndex ?? 0) + 1}`;

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
        if (page === null) {
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
    }, [page, onClose, handlePrevious, handleNext]);

    if (page === null || typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <div className="menu-book-lightbox" role="dialog" aria-modal="true" aria-label={label}>
            <button type="button" className="menu-book-lightbox-backdrop" aria-label="Close menu viewer" onClick={onClose} />

            <div className="menu-book-lightbox-panel">
                <button type="button" className="menu-book-lightbox-close" aria-label="Close" onClick={onClose}>
                    <CloseIcon />
                </button>

                <p className="menu-book-lightbox-counter" aria-live="polite">
                    {activeIndex + 1} / {pages.length}
                </p>

                {hasPrevious ? (
                    <button
                        type="button"
                        className="menu-book-lightbox-nav menu-book-lightbox-nav-prev"
                        aria-label="Previous menu page"
                        onClick={handlePrevious}
                    >
                        <ChevronIcon direction="left" />
                    </button>
                ) : null}

                <figure className="menu-book-lightbox-figure">
                    <img src={page.image} alt={page.alt || label} className="menu-book-lightbox-image" />
                    {page.title ? (
                        <figcaption className="menu-book-lightbox-caption">
                            <p className="font-display text-lg font-semibold text-white">{page.title}</p>
                        </figcaption>
                    ) : null}
                </figure>

                {hasNext ? (
                    <button
                        type="button"
                        className="menu-book-lightbox-nav menu-book-lightbox-nav-next"
                        aria-label="Next menu page"
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
