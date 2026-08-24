import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { createPortal } from 'react-dom';
import { usePrefersReducedMotion } from '../../hooks/useInView';

function CloseIcon() {
    return (
        <svg viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path d="M6 6l12 12M18 6 6 18" strokeLinecap="round" />
        </svg>
    );
}

export default function WelcomePopupModal({ open, popup, onClose, preview = false }) {
    const reducedMotion = usePrefersReducedMotion();
    const [visible, setVisible] = useState(false);
    const [closing, setClosing] = useState(false);
    const closeTimer = useRef(null);

    useEffect(() => {
        if (open) {
            setClosing(false);
            setVisible(true);
            return undefined;
        }

        if (visible) {
            setClosing(true);
            closeTimer.current = window.setTimeout(() => {
                setVisible(false);
                setClosing(false);
            }, reducedMotion ? 0 : 220);
        }

        return () => {
            if (closeTimer.current) {
                window.clearTimeout(closeTimer.current);
            }
        };
    }, [open, reducedMotion, visible]);

    useEffect(() => {
        if (!visible || preview) {
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
    }, [visible, preview, onClose]);

    if (!visible || !popup) {
        return null;
    }

    const hasImage = Boolean(popup.image?.url);
    const hasCta = Boolean(popup.button_text && popup.button_url);
    const headingId = preview ? 'welcome-popup-preview-title' : 'welcome-popup-title';

    const panel = (
        <div
            className={[
                'welcome-popup',
                preview ? 'welcome-popup-preview' : '',
                closing ? 'is-closing' : 'is-open',
                reducedMotion ? 'is-reduced-motion' : '',
            ]
                .filter(Boolean)
                .join(' ')}
            role="dialog"
            aria-modal="true"
            aria-labelledby={headingId}
        >
            {!preview ? (
                <button type="button" className="welcome-popup-backdrop" aria-label="Close welcome popup" onClick={onClose} />
            ) : (
                <div className="welcome-popup-backdrop welcome-popup-backdrop-static" aria-hidden="true" />
            )}

            <div className={`welcome-popup-panel ${hasImage ? 'has-image' : 'is-text-only'}`}>
                <button type="button" className="welcome-popup-close" aria-label="Close" onClick={onClose}>
                    <CloseIcon />
                </button>

                <div className="welcome-popup-grid">
                    {hasImage ? (
                        <div className="welcome-popup-media">
                            <img src={popup.image.url} alt="" className="welcome-popup-image" loading="lazy" />
                        </div>
                    ) : null}

                    <div className="welcome-popup-content">
                        {popup.badge_text ? (
                            <span className="welcome-popup-badge">{popup.badge_text}</span>
                        ) : null}

                        {popup.heading ? (
                            <p className="welcome-popup-eyebrow">{popup.heading}</p>
                        ) : null}

                        {popup.message ? (
                            <h2 id={headingId} className="welcome-popup-title">
                                {popup.message}
                            </h2>
                        ) : null}

                        {popup.description ? (
                            <p className="welcome-popup-description">{popup.description}</p>
                        ) : null}

                        {hasCta ? (
                            preview ? (
                                <span className="btn-orange btn-shine welcome-popup-cta">{popup.button_text}</span>
                            ) : (
                                <Link to={popup.button_url} className="btn-orange btn-shine welcome-popup-cta" onClick={onClose}>
                                    {popup.button_text}
                                    <span className="btn-arrow" aria-hidden="true">
                                        →
                                    </span>
                                </Link>
                            )
                        ) : null}
                    </div>
                </div>
            </div>
        </div>
    );

    if (preview || typeof document === 'undefined') {
        return panel;
    }

    return createPortal(panel, document.body);
}
