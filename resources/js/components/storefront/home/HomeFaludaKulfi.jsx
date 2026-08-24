import { Link } from 'react-router-dom';
import { useInView } from '../../../hooks/useInView';

/**
 * Prefer a product image that is not already used elsewhere on the homepage.
 * Falls back to the first available image in the pillar.
 */
function resolveFeatureVisual(pillar, avoidUrls = []) {
    const products = pillar?.products ?? [];
    const withImages = products.filter((product) => product.image?.url);
    const preferred =
        withImages.find((product) => !avoidUrls.includes(product.image.url)) ?? withImages[0] ?? null;

    return {
        imageUrl: preferred?.image?.url ?? pillar?.coverImage ?? null,
        imageAlt: preferred?.image?.alt_text || preferred?.name || pillar?.coverAlt || pillar?.label,
        href: pillar?.href ?? '/menu',
        label: pillar?.label ?? '',
        description:
            preferred?.short_description ||
            pillar?.blurb ||
            'A signature dessert favorite from Pebbles.',
    };
}

function SignatureReveal({ children, direction = 'left' }) {
    const { ref, isVisible } = useInView();

    return (
        <div
            ref={ref}
            className={[
                direction === 'right' ? 'signature-reveal-right' : 'signature-reveal-left',
                isVisible ? 'is-visible' : '',
            ]
                .filter(Boolean)
                .join(' ')}
        >
            {children}
        </div>
    );
}

function SectionReveal({ children }) {
    const { ref, isVisible } = useInView();

    return (
        <div ref={ref} className={['hp-scroll', isVisible ? 'is-visible' : ''].filter(Boolean).join(' ')}>
            {children}
        </div>
    );
}

export default function HomeFaludaKulfi({ shakes, faluda, avoidImageUrls = [], content = {} }) {
    if (!shakes?.available && !faluda?.available) {
        return null;
    }

    const shakeVisual = shakes?.available ? resolveFeatureVisual(shakes, avoidImageUrls) : null;
    const faludaAvoid = [...avoidImageUrls, ...(shakeVisual?.imageUrl ? [shakeVisual.imageUrl] : [])];
    const faludaVisual = faluda?.available ? resolveFeatureVisual(faluda, faludaAvoid) : null;

    const features = [
        shakeVisual
            ? {
                  key: 'shakes',
                  ...shakeVisual,
                  tone: 'orange',
                  cta: content.shake_cta ?? 'Explore Shakes',
                  direction: 'left',
              }
            : null,
        faludaVisual
            ? {
                  key: 'faluda',
                  ...faludaVisual,
                  tone: 'green',
                  cta: content.faluda_cta ?? 'Explore Faluda',
                  direction: 'right',
              }
            : null,
    ].filter(Boolean);

    if (!features.length) {
        return null;
    }

    return (
        <section className="hp-section relative overflow-hidden py-16 sm:py-20" id="signature-desserts">
            <div className="pointer-events-none absolute inset-0 bg-gradient-to-br from-brand-orange/8 via-transparent to-brand-green/10" />
            <div className="pointer-events-none absolute -left-16 top-24 size-48 rounded-full bg-brand-orange/15 blur-3xl" />
            <div className="pointer-events-none absolute -right-12 bottom-16 size-56 rounded-full bg-brand-green/15 blur-3xl" />

            <div className="relative mx-auto max-w-6xl px-4 sm:px-6">
                <SectionReveal>
                    <div className="hp-section-head mx-auto mb-10 max-w-2xl text-center">
                        <p className="hp-section-eyebrow text-xs font-semibold tracking-widest text-brand-orange uppercase">
                            {content.eyebrow ?? 'Signature desserts'}
                        </p>
                        <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                            {content.title ?? 'Shakes & Faluda'}
                        </h2>
                        <p className="mt-3 text-brand-muted">
                            {content.description ??
                                'Rich creamy shakes and colorful layered faluda — two irresistible favorites from Pebbles.'}
                        </p>
                    </div>
                </SectionReveal>

                <div className="grid gap-6 lg:grid-cols-2 lg:gap-8">
                    {features.map((feature) => (
                        <SignatureReveal key={feature.key} direction={feature.direction}>
                            <Link
                                to={feature.href}
                                className={`signature-card signature-card-${feature.tone} group`}
                            >
                                <div className="signature-card-media">
                                    {feature.imageUrl ? (
                                        <img src={feature.imageUrl} alt={feature.imageAlt} loading="lazy" />
                                    ) : (
                                        <div
                                            className={`flex size-full items-center justify-center text-white ${
                                                feature.tone === 'orange'
                                                    ? 'bg-gradient-to-br from-brand-orange to-[#ea580c]'
                                                    : 'bg-gradient-to-br from-brand-green to-brand-green-light'
                                            }`}
                                        >
                                            <p className="font-display text-3xl font-semibold">{feature.label}</p>
                                        </div>
                                    )}
                                    <div className="signature-card-overlay" />
                                </div>

                                <div className="signature-card-panel">
                                    <span className="signature-card-tag">{feature.label}</span>
                                    <h3 className="font-display text-3xl font-semibold text-white drop-shadow-sm sm:text-4xl">
                                        {feature.label}
                                    </h3>
                                    <p className="mt-2 max-w-md text-sm leading-relaxed text-white/90 sm:text-base">
                                        {feature.description}
                                    </p>
                                    <span className="signature-card-cta mt-5">
                                        {feature.cta}
                                        <span className="btn-arrow" aria-hidden="true">
                                            →
                                        </span>
                                    </span>
                                </div>
                            </Link>
                        </SignatureReveal>
                    ))}
                </div>
            </div>
        </section>
    );
}
