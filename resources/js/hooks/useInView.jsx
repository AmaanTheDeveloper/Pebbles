import { useEffect, useRef, useState } from 'react';

/**
 * Lightweight scroll-reveal helper (IntersectionObserver).
 * Prefer CSS transitions — no animation libraries.
 */
export function useInView({ once = true, rootMargin = '0px 0px -8% 0px', threshold = 0.12 } = {}) {
    const ref = useRef(null);
    const [isVisible, setIsVisible] = useState(false);

    useEffect(() => {
        const node = ref.current;
        if (!node) {
            return undefined;
        }

        if (typeof window === 'undefined' || !('IntersectionObserver' in window)) {
            setIsVisible(true);
            return undefined;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setIsVisible(true);
                    if (once) {
                        observer.unobserve(node);
                    }
                } else if (!once) {
                    setIsVisible(false);
                }
            },
            { rootMargin, threshold },
        );

        observer.observe(node);

        return () => observer.disconnect();
    }, [once, rootMargin, threshold]);

    return { ref, isVisible };
}

export function usePrefersReducedMotion() {
    const [reduced, setReduced] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined' || !window.matchMedia) {
            return undefined;
        }

        const media = window.matchMedia('(prefers-reduced-motion: reduce)');
        const update = () => setReduced(media.matches);
        update();
        media.addEventListener('change', update);

        return () => media.removeEventListener('change', update);
    }, []);

    return reduced;
}

function delayClassForIndex(index, max = 8) {
    const step = Math.min(Math.max(index, 0), max);

    return step > 0 ? `hp-scroll-delay-${step}` : '';
}

export function ScrollReveal({ children, className = '', delayClass = '', variant = 'fade-up' }) {
    const { ref, isVisible } = useInView();
    const variantClass = variant === 'scale' ? 'hp-scroll-scale' : 'hp-scroll';

    return (
        <div
            ref={ref}
            className={[variantClass, delayClass, isVisible ? 'is-visible' : '', className].filter(Boolean).join(' ')}
        >
            {children}
        </div>
    );
}

/** Staggered grid item — pass index for 80ms-step delays (capped at 8). */
export function ScrollRevealItem({ children, index = 0, className = '', variant = 'fade-up' }) {
    return (
        <ScrollReveal delayClass={delayClassForIndex(index)} className={className} variant={variant}>
            {children}
        </ScrollReveal>
    );
}
