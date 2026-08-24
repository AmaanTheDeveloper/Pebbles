import { useId } from 'react';

/**
 * Premium soft-serve ice cream cone preparation animation for order tracking.
 * Modes: waiting | preparing | complete | cancelled
 */
export default function IceCreamMakingAnimation({ mode = 'preparing', className = '' }) {
    const uid = useId().replace(/:/g, '');

    return (
        <div
            className={['ice-cream-making', `is-${mode}`, className].filter(Boolean).join(' ')}
            aria-hidden="true"
        >
            <svg
                viewBox="0 0 180 220"
                className="ice-cream-making-svg h-auto w-full max-w-[140px]"
                role="img"
                aria-label="Ice cream cone being prepared"
            >
                <defs>
                    <linearGradient id={`${uid}-cream-base`} x1="18%" y1="100%" x2="82%" y2="0%">
                        <stop offset="0%" stopColor="#fde68a" />
                        <stop offset="38%" stopColor="#fffdf8" />
                        <stop offset="72%" stopColor="#fef3c7" />
                        <stop offset="100%" stopColor="#fdba74" />
                    </linearGradient>
                    <linearGradient id={`${uid}-cream-mid`} x1="0%" y1="100%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#fde68a" stopOpacity="0.9" />
                        <stop offset="100%" stopColor="#ffffff" />
                    </linearGradient>
                    <linearGradient id={`${uid}-cream-upper`} x1="50%" y1="100%" x2="50%" y2="0%">
                        <stop offset="0%" stopColor="#fff7ed" />
                        <stop offset="100%" stopColor="#ffffff" />
                    </linearGradient>
                    <linearGradient id={`${uid}-cream-tip`} x1="30%" y1="100%" x2="70%" y2="0%">
                        <stop offset="0%" stopColor="#ffffff" />
                        <stop offset="100%" stopColor="#fde68a" stopOpacity="0.85" />
                    </linearGradient>
                    <linearGradient id={`${uid}-cone`} x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stopColor="#fbbf24" />
                        <stop offset="45%" stopColor="#d97706" />
                        <stop offset="100%" stopColor="#92400e" />
                    </linearGradient>
                    <linearGradient id={`${uid}-cone-rim`} x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#fde68a" stopOpacity="0.45" />
                        <stop offset="50%" stopColor="#ffffff" stopOpacity="0.9" />
                        <stop offset="100%" stopColor="#fde68a" stopOpacity="0.45" />
                    </linearGradient>
                    <linearGradient id={`${uid}-drizzle`} x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stopColor="#78350f" />
                        <stop offset="100%" stopColor="#92400e" />
                    </linearGradient>
                    <radialGradient id={`${uid}-ambient`} cx="50%" cy="40%" r="58%">
                        <stop offset="0%" stopColor="#fffdf8" stopOpacity="0.8" />
                        <stop offset="100%" stopColor="#fffdf8" stopOpacity="0" />
                    </radialGradient>
                    <radialGradient id={`${uid}-shadow`} cx="50%" cy="50%" r="50%">
                        <stop offset="0%" stopColor="#176b3a" stopOpacity="0.2" />
                        <stop offset="100%" stopColor="#176b3a" stopOpacity="0" />
                    </radialGradient>
                    <linearGradient id={`${uid}-shine`} x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" stopColor="#ffffff" stopOpacity="0" />
                        <stop offset="42%" stopColor="#ffffff" stopOpacity="0.62" />
                        <stop offset="58%" stopColor="#ffffff" stopOpacity="0.62" />
                        <stop offset="100%" stopColor="#ffffff" stopOpacity="0" />
                    </linearGradient>
                    <clipPath id={`${uid}-build-zone`}>
                        <rect className="ice-cream-making-fill-mask" x="0" y="0" width="180" height="220" />
                    </clipPath>
                    <clipPath id={`${uid}-cream-surface`}>
                        <path d="M54 136 C66 118 76 96 90 72 C104 96 114 118 126 136 C114 130 102 126 90 126 C78 126 66 130 54 136 Z" />
                    </clipPath>
                </defs>

                <g className="ice-cream-making-ambient">
                    <ellipse cx="90" cy="104" rx="62" ry="60" fill={`url(#${uid}-ambient)`} />
                    <circle className="ice-cream-making-particle ice-cream-making-particle-1" cx="34" cy="68" r="2" fill="#fde68a" opacity="0.4" />
                    <circle className="ice-cream-making-particle ice-cream-making-particle-2" cx="146" cy="84" r="1.5" fill="#f97316" opacity="0.32" />
                    <circle className="ice-cream-making-particle ice-cream-making-particle-3" cx="44" cy="144" r="1.3" fill="#176b3a" opacity="0.18" />
                    <circle className="ice-cream-making-particle ice-cream-making-particle-4" cx="138" cy="128" r="1.7" fill="#fffdf8" opacity="0.45" />
                </g>

                <ellipse
                    className="ice-cream-making-shadow"
                    cx="90"
                    cy="206"
                    rx="36"
                    ry="6.5"
                    fill={`url(#${uid}-shadow)`}
                />

                <g className="ice-cream-making-scene">
                    <g className="ice-cream-making-cone">
                        <path
                            d="M58 136 L90 204 L122 136 Z"
                            fill={`url(#${uid}-cone)`}
                            stroke="#176b3a"
                            strokeWidth="2"
                            strokeLinejoin="round"
                        />
                        <path
                            d="M64 146 L76 160 L88 174 L100 160 L112 146 M70 156 L82 170 L98 170 L110 156 M76 166 L90 182 L104 166"
                            fill="none"
                            stroke="#78350f"
                            strokeWidth="0.85"
                            opacity="0.28"
                        />
                        <path
                            d="M62 136 Q90 129 118 136"
                            fill="none"
                            stroke={`url(#${uid}-cone-rim)`}
                            strokeWidth="2.5"
                            strokeLinecap="round"
                        />
                        <path
                            d="M66 140 L72 152"
                            fill="none"
                            stroke="#ffffff"
                            strokeWidth="1.1"
                            strokeLinecap="round"
                            opacity="0.38"
                        />
                    </g>

                    <path
                        className="ice-cream-making-pipe-stream"
                        d="M90 6 L90 48"
                        fill="none"
                        stroke="#fffdf8"
                        strokeWidth="4.5"
                        strokeLinecap="round"
                        opacity="0.88"
                    />

                    <g clipPath={`url(#${uid}-build-zone)`}>
                        <g className="ice-cream-making-layer ice-cream-making-layer-1">
                            <path
                                d="M90 136 C70 124 56 106 62 90 C68 74 78 66 90 58 C102 50 116 54 120 68 C124 82 114 96 98 108 C86 118 80 128 90 136 Z"
                                fill={`url(#${uid}-cream-base)`}
                                stroke="#176b3a"
                                strokeWidth="1.4"
                                strokeLinejoin="round"
                            />
                        </g>

                        <g className="ice-cream-making-layer ice-cream-making-layer-2">
                            <path
                                d="M90 118 C80 110 72 100 76 90 C80 80 84 74 90 70 C96 74 100 80 104 90 C108 100 100 110 90 118 Z"
                                fill={`url(#${uid}-cream-mid)`}
                                stroke="#176b3a"
                                strokeWidth="1.2"
                                strokeLinejoin="round"
                            />
                            <path
                                d="M82 108 C86 100 88 94 90 88 C92 94 94 100 98 108"
                                fill="none"
                                stroke="#fde68a"
                                strokeWidth="1"
                                opacity="0.4"
                            />
                        </g>

                        <g className="ice-cream-making-layer ice-cream-making-layer-3">
                            <path
                                d="M90 98 C84 92 80 84 84 76 C88 68 88 64 90 60 C92 64 92 68 96 76 C100 84 96 92 90 98 Z"
                                fill={`url(#${uid}-cream-upper)`}
                                stroke="#176b3a"
                                strokeWidth="1.1"
                                strokeLinejoin="round"
                            />
                        </g>

                        <g className="ice-cream-making-layer ice-cream-making-layer-4">
                            <path
                                d="M74 68 Q90 48 106 68 Q98 76 90 72 Q82 76 74 68 Z"
                                fill={`url(#${uid}-cream-mid)`}
                                stroke="#176b3a"
                                strokeWidth="1.1"
                                strokeLinejoin="round"
                            />
                        </g>

                        <g className="ice-cream-making-layer ice-cream-making-layer-5">
                            <path
                                d="M66 54 Q90 28 114 54 Q104 64 90 58 Q76 64 66 54 Z"
                                fill={`url(#${uid}-cream-tip)`}
                                stroke="#176b3a"
                                strokeWidth="1.1"
                                strokeLinejoin="round"
                            />
                            <ellipse cx="80" cy="46" rx="7" ry="3.5" fill="#ffffff" opacity="0.58" />
                        </g>

                        <path
                            className="ice-cream-making-drip"
                            d="M106 94 Q110 102 108 110"
                            fill="none"
                            stroke="#fffdf8"
                            strokeWidth="2.2"
                            strokeLinecap="round"
                            opacity="0.65"
                        />

                        <g className="ice-cream-making-gloss" clipPath={`url(#${uid}-cream-surface)`}>
                            <ellipse cx="76" cy="90" rx="13" ry="20" fill="#ffffff" opacity="0.24" />
                            <ellipse cx="98" cy="78" rx="6" ry="10" fill="#ffffff" opacity="0.14" />
                        </g>
                    </g>

                    <g className="ice-cream-making-drizzle-group">
                        <path
                            className="ice-cream-making-drizzle ice-cream-making-drizzle-main"
                            d="M76 68 C84 78 92 74 100 62"
                            fill="none"
                            stroke={`url(#${uid}-drizzle)`}
                            strokeWidth="2"
                            strokeLinecap="round"
                        />
                        <path
                            className="ice-cream-making-drizzle ice-cream-making-drizzle-tail"
                            d="M92 74 C94 82 90 88 86 92"
                            fill="none"
                            stroke={`url(#${uid}-drizzle)`}
                            strokeWidth="1.5"
                            strokeLinecap="round"
                            opacity="0.85"
                        />
                    </g>

                    <g className="ice-cream-making-sprinkles">
                        <rect className="ice-cream-making-sprinkle ice-cream-making-sprinkle-1" x="72" y="40" width="3" height="1.2" rx="0.6" fill="#176b3a" transform="rotate(-22 73.5 40.6)" />
                        <rect className="ice-cream-making-sprinkle ice-cream-making-sprinkle-2" x="84" y="36" width="3" height="1.2" rx="0.6" fill="#f97316" transform="rotate(16 85.5 36.6)" />
                        <rect className="ice-cream-making-sprinkle ice-cream-making-sprinkle-3" x="94" y="42" width="3" height="1.2" rx="0.6" fill="#176b3a" transform="rotate(-6 95.5 42.6)" />
                        <rect className="ice-cream-making-sprinkle ice-cream-making-sprinkle-4" x="100" y="48" width="2.6" height="1.1" rx="0.55" fill="#fb923c" transform="rotate(28 101.3 48.55)" />
                        <rect className="ice-cream-making-sprinkle ice-cream-making-sprinkle-5" x="76" y="48" width="2.6" height="1.1" rx="0.55" fill="#fb923c" transform="rotate(-12 77.3 48.55)" />
                    </g>

                    <circle
                        className="ice-cream-making-accent"
                        cx="96"
                        cy="44"
                        r="3"
                        fill="#78350f"
                        stroke="#176b3a"
                        strokeWidth="0.7"
                    />

                    <g className="ice-cream-making-shine" clipPath={`url(#${uid}-cream-surface)`}>
                        <rect
                            x="-44"
                            y="56"
                            width="34"
                            height="72"
                            fill={`url(#${uid}-shine)`}
                            transform="rotate(-16 90 92)"
                        />
                    </g>
                </g>
            </svg>
        </div>
    );
}

/** Alias for clearer imports */
export { IceCreamMakingAnimation as IceCreamConePreparationAnimation };
