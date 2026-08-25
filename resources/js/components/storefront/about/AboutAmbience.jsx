import { ScrollReveal, ScrollRevealItem } from '../../../hooks/useInView';

export default function AboutAmbience({ imageUrl, imageAlt, secondaryImageUrl, secondaryImageAlt, content }) {
    const ambience = content ?? {};
    const points = Array.isArray(ambience.points) ? ambience.points : [];

    return (
        <section className="hp-section relative overflow-hidden bg-brand-cream/80 py-16 sm:py-20" id="experience">
            <div className="pointer-events-none absolute -left-16 top-20 size-48 rounded-full bg-brand-orange/12 blur-3xl" />
            <div className="pointer-events-none absolute -right-10 bottom-10 size-56 rounded-full bg-brand-green/12 blur-3xl" />

            <div className="relative mx-auto max-w-6xl px-4 sm:px-6">
                <ScrollReveal>
                    <div className="hp-section-head mx-auto mb-10 max-w-2xl text-center">
                        <p className="hp-section-eyebrow text-xs font-semibold text-brand-green uppercase">{ambience.eyebrow}</p>
                        <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">{ambience.title}</h2>
                        <p className="mt-3 text-brand-muted">{ambience.subtitle}</p>
                    </div>
                </ScrollReveal>

                <div className="grid items-stretch gap-6 lg:grid-cols-5 lg:gap-8">
                    <ScrollReveal className="lg:col-span-3">
                        <div className="hero-media relative h-full min-h-72 overflow-hidden rounded-[1.75rem] shadow-xl ring-1 ring-brand-green/10">
                            {imageUrl ? (
                                <img
                                    src={imageUrl}
                                    alt={imageAlt || 'Pebbles ambience'}
                                    className="size-full min-h-72 object-cover"
                                    loading="lazy"
                                />
                            ) : (
                                <div className="flex size-full min-h-72 items-center justify-center bg-gradient-to-br from-brand-green to-brand-green-light p-8 text-center text-white">
                                    <p className="font-display text-3xl font-semibold">Good vibes inside</p>
                                </div>
                            )}
                        </div>
                    </ScrollReveal>

                    <div className="flex flex-col gap-4 lg:col-span-2">
                        {points.map((point, index) => (
                            <ScrollRevealItem key={point.title} index={index + 1} variant="scale">
                                <article className="lift-card lift-card-soft rounded-2xl bg-white p-5 ring-1 ring-brand-green/10">
                                    <h3 className="font-display text-lg font-semibold text-brand-ink">{point.title}</h3>
                                    <p className="mt-2 text-sm leading-relaxed text-brand-muted">{point.text}</p>
                                </article>
                            </ScrollRevealItem>
                        ))}

                        {secondaryImageUrl ? (
                            <ScrollReveal delayClass="hp-scroll-delay-2">
                                <div className="about-team-media relative overflow-hidden rounded-2xl ring-1 ring-brand-green/10">
                                    <img
                                        src={secondaryImageUrl}
                                        alt={secondaryImageAlt || 'Pebbles treats'}
                                        className="aspect-[16/10] w-full object-cover"
                                        loading="lazy"
                                    />
                                </div>
                            </ScrollReveal>
                        ) : null}
                    </div>
                </div>
            </div>
        </section>
    );
}
