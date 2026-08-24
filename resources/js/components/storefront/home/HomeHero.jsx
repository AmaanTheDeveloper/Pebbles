import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import AppImage from '../../performance/AppImage';
import { appName } from '../../../api/config';
import { usePrefersReducedMotion } from '../../../hooks/useInView';

const SIDE_IMAGE_DELAYS = ['hp-reveal-delay-1', 'hp-reveal-delay-2', 'hp-reveal-delay-3'];

export default function HomeHero({ heroProduct, pillars = [], content = {} }) {
    const imageUrl = content.hero_image?.url ?? heroProduct?.image?.url;
    const reducedMotion = usePrefersReducedMotion();
    const mediaRef = useRef(null);
    const [parallaxY, setParallaxY] = useState(0);

    const sideImages = pillars
        .map((pillar) => ({
            key: pillar.key,
            label: pillar.label,
            url: pillar.coverImage,
            alt: pillar.coverAlt,
            href: pillar.href,
        }))
        .filter((item) => item.url)
        .slice(0, 3);

    useEffect(() => {
        if (reducedMotion || typeof window === 'undefined') {
            return undefined;
        }

        let frame = 0;

        const onScroll = () => {
            if (frame) {
                return;
            }

            frame = window.requestAnimationFrame(() => {
                frame = 0;
                const node = mediaRef.current;
                if (!node) {
                    return;
                }

                const rect = node.getBoundingClientRect();
                const viewportHeight = window.innerHeight || 1;
                const progress = 1 - Math.min(Math.max(rect.top / viewportHeight, 0), 1);
                setParallaxY(progress * 14);
            });
        };

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => {
            window.removeEventListener('scroll', onScroll);
            if (frame) {
                window.cancelAnimationFrame(frame);
            }
        };
    }, [reducedMotion]);

    return (
        <section className="relative overflow-hidden border-b border-brand-green/10">
            <div className="pointer-events-none absolute inset-0">
                <div className="hp-float absolute -top-16 -left-10 size-56 rounded-full bg-brand-green/15 blur-2xl" />
                <div className="hp-float-slow absolute top-24 right-10 size-44 rounded-full bg-brand-orange/20 blur-2xl [animation-delay:1.2s]" />
                <div className="hp-float absolute right-1/4 bottom-10 size-28 rounded-full bg-brand-orange/15 blur-xl [animation-delay:2s]" />
                <div className="hero-glow-pulse absolute bottom-0 left-1/3 size-72 rounded-full bg-brand-green-light/10 blur-3xl" />
            </div>

            <div className="relative mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-14 lg:py-24">
                <div className="space-y-6">
                    <p className="hp-reveal inline-flex items-center gap-2 rounded-full border border-brand-green/15 bg-white/70 px-3 py-1.5 text-xs font-semibold tracking-wide text-brand-green uppercase shadow-sm backdrop-blur-sm">
                        <span className="size-1.5 rounded-full bg-brand-orange" />
                        {content.badge ?? `${appName} dessert bar`}
                    </p>
                    <h1 className="hp-reveal hp-reveal-delay-1 font-display max-w-xl text-4xl leading-[1.08] font-semibold tracking-tight text-brand-ink sm:text-5xl lg:text-6xl">
                        {content.heading_line1 ?? 'Ice Cream. Shakes.'}
                        <span className="mt-1 block bg-gradient-to-r from-brand-orange via-[#fb923c] to-[#ea580c] bg-clip-text text-transparent">
                            {content.heading_accent ?? 'Faluda. Kulfi.'}
                        </span>
                    </h1>
                    <p className="hp-reveal hp-reveal-delay-2 max-w-lg text-base leading-relaxed text-brand-muted sm:text-lg">
                        {content.subtitle ??
                            'Premium scoops, rich shakes, layered faluda, and creamy kulfi — order takeaway or delivery from your nearest branch.'}
                    </p>
                    <div className="hp-reveal hp-reveal-delay-3 flex flex-wrap gap-3">
                        <Link to={content.primary_cta_url ?? '/order'} className="btn-orange btn-shine px-6 py-3 text-sm">
                            {content.primary_cta_text ?? 'Order Online'}
                            <span className="btn-arrow" aria-hidden="true">
                                →
                            </span>
                        </Link>
                        <Link to={content.secondary_cta_url ?? '/menu'} className="btn-ghost-green px-6 py-3 text-sm">
                            {content.secondary_cta_text ?? 'Explore Menu'}
                            <span className="btn-arrow" aria-hidden="true">
                                →
                            </span>
                        </Link>
                    </div>
                    <ul className="hp-reveal hp-reveal-delay-4 flex flex-wrap gap-2 pt-1">
                        {(content.chips ?? ['Ice Cream', 'Shakes', 'Faluda', 'Kulfi']).map((label) => (
                            <li
                                key={label}
                                className="hero-chip rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-brand-green ring-1 ring-brand-green/15"
                            >
                                {label}
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="hp-reveal hp-reveal-delay-2 relative mx-auto w-full max-w-md lg:max-w-none">
                    <div className="hero-glow-pulse absolute -inset-3 rounded-[2rem] bg-gradient-to-br from-brand-green/25 via-transparent to-brand-orange/30 blur-md" />
                    <div
                        ref={mediaRef}
                        className="hero-media hero-image-float relative overflow-hidden rounded-[1.75rem] bg-white shadow-2xl shadow-brand-green/20 ring-1 ring-brand-green/10"
                    >
                        <div
                            className="hero-media-inner aspect-[4/5] overflow-hidden sm:aspect-square"
                            style={reducedMotion ? undefined : { transform: `translateY(${parallaxY}px)` }}
                        >
                            {imageUrl ? (
                                <AppImage
                                    src={imageUrl}
                                    alt={heroProduct.image?.alt_text || heroProduct.name}
                                    className="size-full object-cover"
                                    wrapperClassName="size-full"
                                    highPriority
                                    eager
                                />
                            ) : (
                                <div className="flex size-full flex-col items-center justify-center gap-3 bg-gradient-to-br from-brand-green to-brand-green-light p-8 text-center text-white">
                                    <p className="font-display text-3xl font-semibold">{appName}</p>
                                    <p className="text-sm text-white/85">Scoops, shakes, faluda & kulfi.</p>
                                </div>
                            )}
                        </div>
                        {heroProduct ? (
                            <div className="flex items-center justify-between gap-3 border-t border-brand-green/10 bg-white/90 px-5 py-4 backdrop-blur-sm">
                                <div>
                                    <p className="text-xs font-semibold tracking-wide text-brand-orange uppercase">
                                        {content.featured_label ?? 'Featured treat'}
                                    </p>
                                    <p className="font-display text-lg font-semibold text-brand-ink">{heroProduct.name}</p>
                                </div>
                                <Link to={`/product/${heroProduct.slug}`} className="btn-green px-4 py-2 text-xs">
                                    View
                                    <span className="btn-arrow" aria-hidden="true">
                                        →
                                    </span>
                                </Link>
                            </div>
                        ) : null}
                    </div>

                    {sideImages.length ? (
                        <div className="mt-4 grid grid-cols-3 gap-3">
                            {sideImages.map((item, index) => (
                                <Link
                                    key={item.key}
                                    to={item.href}
                                    className={[
                                        'dessert-chip group overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-brand-green/10 hp-reveal',
                                        SIDE_IMAGE_DELAYS[index] ?? 'hp-reveal-delay-3',
                                    ].join(' ')}
                                >
                                    <div className="aspect-square overflow-hidden">
                                        <img
                                            src={item.url}
                                            alt={item.alt}
                                            className="size-full object-cover transition duration-500 group-hover:scale-110"
                                            loading="lazy"
                                        />
                                    </div>
                                    <p className="px-2 py-1.5 text-center text-[11px] font-semibold text-brand-ink">
                                        {item.label}
                                    </p>
                                </Link>
                            ))}
                        </div>
                    ) : null}
                </div>
            </div>
        </section>
    );
}
