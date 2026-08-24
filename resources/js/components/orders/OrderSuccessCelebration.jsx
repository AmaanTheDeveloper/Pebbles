import { useEffect, useRef } from 'react';
import { playOrderSuccessSound, spawnOrderSuccessParticles } from '../../lib/orderSuccessCelebration';

export default function OrderSuccessCelebration({ active = false }) {
    const particleHostRef = useRef(null);
    const hasPlayedRef = useRef(false);

    useEffect(() => {
        if (!active || hasPlayedRef.current) {
            return undefined;
        }

        hasPlayedRef.current = true;
        playOrderSuccessSound();

        const cleanupParticles = spawnOrderSuccessParticles(particleHostRef.current);

        return () => {
            cleanupParticles?.();
        };
    }, [active]);

    return (
        <div
            className="relative mx-auto flex w-full max-w-[220px] flex-col items-center justify-center"
            aria-hidden="true"
        >
            <div ref={particleHostRef} className="pointer-events-none absolute inset-0 flex items-center justify-center" />

            <div
                className={[
                    'order-success-badge relative flex size-28 items-center justify-center rounded-full',
                    active ? 'order-success-badge-active' : '',
                ].join(' ')}
            >
                <span className="order-success-glow absolute inset-0 rounded-full" aria-hidden="true" />
                <svg
                    viewBox="0 0 72 72"
                    className="relative z-10 size-[4.5rem]"
                    aria-hidden="true"
                >
                    <circle
                        cx="36"
                        cy="36"
                        r="30"
                        className="order-success-circle-fill"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="3"
                    />
                    <path
                        d="M22 37 L32 47 L50 27"
                        className="order-success-check-stroke"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="4.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </div>
        </div>
    );
}
