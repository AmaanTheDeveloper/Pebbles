import { ScrollReveal } from '../../../hooks/useInView';

const defaultServices = [
    {
        title: 'Fresh ingredients',
        description: 'Desserts prepared with care for flavor you can taste in every scoop and sip.',
        accent: 'from-brand-green/15 to-brand-green/5',
        icon: 'F',
        glow: 'lift-card-green',
    },
    {
        title: 'Rich flavors',
        description: 'Ice cream, shakes, faluda, and kulfi crafted for real dessert cravings.',
        accent: 'from-brand-orange/20 to-brand-orange/5',
        icon: 'R',
        glow: 'lift-card-orange',
    },
    {
        title: 'Easy ordering',
        description: 'Takeaway or delivery from your nearest branch — order online in a few taps.',
        accent: 'from-brand-green-light/20 to-brand-cream',
        icon: 'E',
        glow: 'lift-card-green',
    },
];

export default function HomeServices({ features = defaultServices, content = {} }) {
    const services = (features.length ? features : defaultServices).map((feature) => ({
        title: feature.title,
        description: feature.description,
        accent: feature.accent,
        icon: feature.icon_letter ?? feature.icon,
        glow: feature.glow,
    }));

    return (
        <section className="hp-section mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="why-pebbles">
            <ScrollReveal>
                <div className="mx-auto mb-10 max-w-2xl text-center">
                    <p className="text-xs font-semibold tracking-widest text-brand-orange uppercase">
                        {content.eyebrow ?? 'Why Pebbles'}
                    </p>
                    <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                        {content.title ?? 'A dessert destination, not just a stop'}
                    </h2>
                    <p className="mt-3 text-brand-muted">
                        {content.description ??
                            'Premium treats, clear ordering, and flavors built around ice cream, shakes, faluda, and kulfi.'}
                    </p>
                </div>
            </ScrollReveal>
            <div className="grid gap-5 md:grid-cols-3">
                {services.map((service, index) => (
                    <ScrollReveal key={service.title} delayClass={`hp-scroll-delay-${index + 1}`}>
                        <article
                            className={`group lift-card ${service.glow} rounded-3xl bg-gradient-to-br p-6 ring-1 ring-brand-green/10 ${service.accent}`}
                        >
                            <div className="service-icon flex size-12 items-center justify-center rounded-2xl bg-white font-display text-lg font-bold text-brand-green shadow-sm">
                                {service.icon}
                            </div>
                            <h3 className="mt-5 font-display text-xl font-semibold text-brand-ink transition group-hover:text-brand-green">
                                {service.title}
                            </h3>
                            <p className="mt-2 text-sm leading-relaxed text-brand-muted">{service.description}</p>
                            <div className="mt-5 h-0.5 w-8 rounded-full bg-brand-orange/40 transition-all duration-300 group-hover:w-14 group-hover:bg-brand-orange" />
                        </article>
                    </ScrollReveal>
                ))}
            </div>
        </section>
    );
}
