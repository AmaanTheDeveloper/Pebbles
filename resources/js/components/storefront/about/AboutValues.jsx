import { BRAND_VALUES } from '../../../lib/aboutContent';
import { ScrollReveal, ScrollRevealItem } from '../../../hooks/useInView';

export default function AboutValues() {
    return (
        <section className="hp-section bg-gradient-to-b from-white to-[#f3faf6] py-16 sm:py-20" id="values">
            <div className="mx-auto max-w-6xl px-4 sm:px-6">
                <ScrollReveal>
                    <div className="hp-section-head mx-auto mb-10 max-w-2xl text-center">
                        <p className="hp-section-eyebrow text-xs font-semibold text-brand-green uppercase">Core values</p>
                        <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                            What We Stand For
                        </h2>
                        <p className="mt-3 text-brand-muted">
                            The principles that guide every scoop, sip, and smile at Pebbles.
                        </p>
                    </div>
                </ScrollReveal>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {BRAND_VALUES.map((value, index) => (
                        <ScrollRevealItem key={value.title} index={index + 1} variant="scale" className="h-full">
                            <article className="group lift-card lift-card-green flex h-full items-start gap-4 rounded-2xl bg-white p-5 ring-1 ring-brand-green/10">
                                <div className="service-icon flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-green/10 text-xs font-bold text-brand-green">
                                    {value.icon}
                                </div>
                                <div>
                                    <h3 className="font-display text-lg font-semibold text-brand-ink transition group-hover:text-brand-green">
                                        {value.title}
                                    </h3>
                                    <p className="mt-1 text-sm leading-relaxed text-brand-muted">{value.description}</p>
                                </div>
                            </article>
                        </ScrollRevealItem>
                    ))}
                </div>
            </div>
        </section>
    );
}
