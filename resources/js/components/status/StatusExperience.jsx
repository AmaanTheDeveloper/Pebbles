import SiteLogo from '../storefront/SiteLogo';

/**
 * Lightweight ice-cream illustration used by Offline and 404 experiences.
 */
export function MeltingConeIllustration({ className = '', variant = '404' }) {
    const isOffline = variant === 'offline';

    return (
        <div className={['status-illu', className].filter(Boolean).join(' ')} aria-hidden="true">
            <svg viewBox="0 0 200 220" className="status-illu-svg" role="presentation">
                <defs>
                    <linearGradient id="statusScoop" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stopColor="#fb923c" />
                        <stop offset="55%" stopColor="#f97316" />
                        <stop offset="100%" stopColor="#ea580c" />
                    </linearGradient>
                    <linearGradient id="statusCone" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stopColor="#fbbf24" />
                        <stop offset="100%" stopColor="#d97706" />
                    </linearGradient>
                </defs>

                <ellipse className="status-illu-shadow" cx="100" cy="198" rx="42" ry="8" fill="rgb(23 107 58 / 0.12)" />

                <g className="status-illu-float">
                    <path
                        d="M68 118 L100 198 L132 118 Z"
                        fill="url(#statusCone)"
                        stroke="#b45309"
                        strokeWidth="1.5"
                        strokeLinejoin="round"
                    />
                    <path d="M78 132 L122 132" stroke="#b45309" strokeWidth="1" opacity="0.45" />
                    <path d="M84 148 L116 148" stroke="#b45309" strokeWidth="1" opacity="0.4" />
                    <path d="M90 164 L110 164" stroke="#b45309" strokeWidth="1" opacity="0.35" />

                    <circle cx="100" cy="78" r="42" fill="url(#statusScoop)" />
                    <circle cx="78" cy="92" r="22" fill="#fb923c" />
                    <circle cx="122" cy="92" r="22" fill="#ea580c" />
                    <circle cx="88" cy="62" r="7" fill="white" opacity="0.35" />

                    {isOffline ? (
                        <g className="status-illu-signal">
                            <path
                                d="M148 48c10 10 10 26 0 36"
                                fill="none"
                                stroke="#176b3a"
                                strokeWidth="3"
                                strokeLinecap="round"
                                className="status-illu-wave status-illu-wave-1"
                            />
                            <path
                                d="M160 36c16 16 16 42 0 58"
                                fill="none"
                                stroke="#176b3a"
                                strokeWidth="3"
                                strokeLinecap="round"
                                className="status-illu-wave status-illu-wave-2"
                            />
                            <circle cx="148" cy="84" r="4" fill="#f97316" className="status-illu-dot" />
                            <path
                                d="M142 54 L170 82"
                                stroke="#f97316"
                                strokeWidth="3"
                                strokeLinecap="round"
                                className="status-illu-slash"
                            />
                        </g>
                    ) : (
                        <g className="status-illu-drip">
                            <path d="M118 108c0 14 8 22 8 34" fill="none" stroke="#ea580c" strokeWidth="6" strokeLinecap="round" />
                            <circle cx="126" cy="146" r="5" fill="#ea580c" className="status-illu-drip-drop" />
                        </g>
                    )}
                </g>
            </svg>
        </div>
    );
}

/**
 * Shared shell for branded status pages (404 / offline overlay content).
 */
export default function StatusExperience({
    logo = null,
    eyebrow,
    title,
    titleId,
    description,
    illustration = '404',
    children,
    brandLink = true,
}) {
    return (
        <div className="status-page">
            <div className="status-page-glow status-page-glow-a" aria-hidden="true" />
            <div className="status-page-glow status-page-glow-b" aria-hidden="true" />

            <div className="status-page-card hp-reveal">
                <div className="status-page-brand hp-reveal-delay-1">
                    <SiteLogo logo={logo} variant="auth" linkTo={brandLink ? '/' : null} />
                </div>

                {eyebrow ? (
                    <p className="status-page-eyebrow hp-reveal-delay-1">{eyebrow}</p>
                ) : null}

                <MeltingConeIllustration variant={illustration} className="hp-reveal-delay-2" />

                <h1 id={titleId} className="status-page-title hp-reveal-delay-2">
                    {title}
                </h1>
                <p className="status-page-copy hp-reveal-delay-3">{description}</p>

                <div className="status-page-actions hp-reveal-delay-3">{children}</div>
            </div>
        </div>
    );
}
