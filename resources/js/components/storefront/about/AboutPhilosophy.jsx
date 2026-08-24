import { PHILOSOPHY_ITEMS } from '../../../lib/aboutContent';
import { ScrollReveal, ScrollRevealItem } from '../../../hooks/useInView';

export default function AboutPhilosophy() {
    return (
        <section className="hp-section hp-section-divider mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="philosophy">
            <ScrollReveal>
                <div className="hp-section-head mx-auto mb-10 max-w-2xl text-center">
                    <p className="hp-section-eyebrow text-xs font-semibold text-brand-green uppercase">What we believe</p>
                    <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">Made for Moments</h2>
                    <p className="mt-3 text-brand-muted">
                        Great taste, fresh ingredients, and experiences that feel relaxed, creative, and genuinely happy.
                    </p>
                </div>
            </ScrollReveal>

            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {PHILOSOPHY_ITEMS.map((item, index) => (
                    <ScrollRevealItem key={item.title} index={index + 1} variant="scale" className="h-full">
                        <article className="group lift-card lift-card-soft flex h-full flex-col rounded-3xl bg-white p-6 ring-1 ring-brand-green/10">
                            <div className="service-icon flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-green/15 to-brand-orange/10 font-display text-lg font-bold text-brand-green">
                                {item.icon}
                            </div>
                            <h3 className="mt-5 font-display text-xl font-semibold text-brand-ink transition group-hover:text-brand-green">
                                {item.title}
                            </h3>
                            <p className="mt-2 text-sm leading-relaxed text-brand-muted">{item.description}</p>
                        </article>
                    </ScrollRevealItem>
                ))}
            </div>
        </section>
    );
}
