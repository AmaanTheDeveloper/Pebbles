import { useEffect } from 'react';
import { createPortal } from 'react-dom';
import ReviewForm from './ReviewForm';

function CloseIcon() {
    return (
        <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path d="M6 6l12 12M18 6 6 18" strokeLinecap="round" />
        </svg>
    );
}

export default function ReviewSubmissionModal({ open, onClose, onSuccess }) {
    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                onClose();
            }
        };

        window.addEventListener('keydown', onKeyDown);

        return () => {
            document.body.style.overflow = previousOverflow;
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [open, onClose]);

    if (!open || typeof document === 'undefined') {
        return null;
    }

    return createPortal(
        <div className="review-modal" role="dialog" aria-modal="true" aria-labelledby="review-modal-title">
            <button type="button" className="review-modal-backdrop" aria-label="Close review form" onClick={onClose} />

            <div className="review-modal-panel">
                <div className="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs font-semibold tracking-widest text-brand-orange uppercase">Share your story</p>
                        <h2 id="review-modal-title" className="mt-1 font-display text-2xl font-semibold text-brand-ink">
                            Share Your Pebbles Experience
                        </h2>
                        <p className="mt-1 text-sm text-brand-muted">Tried something you loved? Tell us about it.</p>
                    </div>
                    <button type="button" className="review-modal-close" aria-label="Close" onClick={onClose}>
                        <CloseIcon />
                    </button>
                </div>

                <ReviewForm
                    formId="homepage-review-form"
                    onSuccess={(response) => {
                        onSuccess?.(response);
                    }}
                />
            </div>
        </div>,
        document.body,
    );
}
