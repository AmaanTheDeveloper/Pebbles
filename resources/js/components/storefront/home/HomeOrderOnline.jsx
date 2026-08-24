import { Link } from 'react-router-dom';
import { ScrollReveal } from '../../../hooks/useInView';

const defaultCards = [
    {
        title: 'Order Now',
        description: 'Start with ice cream, shakes, faluda, or kulfi — then check out.',
        to: '/order',
        tone: 'orange',
    },
    {
        title: 'Explore Menu',
        description: 'Browse every dessert category on the live Pebbles menu.',
        to: '/menu',
        tone: 'green',
    },
    {
        title: 'Your Cart',
        description: 'Review your treats and continue when you are ready.',
        to: '/cart',
        tone: 'white',
    },
];

export default function HomeOrderOnline({ cards = defaultCards, content = {} }) {
    const displayCards = cards.length ? cards : defaultCards;

    return (
        <section className="hp-section hp-section-divider mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="order-online">
            <ScrollReveal>
                <div className="hp-section-head mx-auto mb-10 max-w-2xl text-center">
                    <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">
                        {content.eyebrow ?? 'Order online'}
                    </p>
                    <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                        {content.title ?? 'Your favorite dessert is just a click away'}
                    </h2>
                    <p className="mt-3 text-brand-muted">
                        {content.description ??
                            'Choose delivery or takeaway, pick your scoop or shake, and place your order in minutes.'}
                    </p>
                </div>
            </ScrollReveal>
            <div className="grid gap-4 md:grid-cols-3">
                {displayCards.map((card, index) => {
                    const styles =
                        card.tone === 'orange'
                            ? 'lift-card lift-card-orange bg-gradient-to-br from-brand-orange to-[#ea580c] text-white'
                            : card.tone === 'green'
                              ? 'lift-card lift-card-green bg-gradient-to-br from-brand-green-light to-brand-green text-white'
                              : 'lift-card lift-card-soft bg-white text-brand-ink ring-1 ring-brand-green/15';

                    return (
                        <ScrollReveal key={card.id ?? card.title} delayClass={`hp-scroll-delay-${index + 1}`}>
                            <Link to={card.link_url ?? card.to} className={`group block rounded-3xl p-6 ${styles}`}>
                                <p className="font-display text-2xl font-semibold">{card.title}</p>
                                <p
                                    className={`mt-2 text-sm leading-relaxed ${card.tone === 'white' ? 'text-brand-muted' : 'text-white/85'}`}
                                >
                                    {card.description}
                                </p>
                                <span
                                    className={`order-card-arrow ${card.tone === 'white' ? 'text-brand-orange' : 'text-white'}`}
                                >
                                    Continue <span aria-hidden="true">→</span>
                                </span>
                            </Link>
                        </ScrollReveal>
                    );
                })}
            </div>
        </section>
    );
}
