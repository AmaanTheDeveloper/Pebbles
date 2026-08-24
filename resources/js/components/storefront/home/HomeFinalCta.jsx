import { Link } from 'react-router-dom';
import { ScrollReveal } from '../../../hooks/useInView';

export default function HomeFinalCta({ content = {} }) {
    return (
        <section className="hp-section hp-section-divider mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="ready">
            <ScrollReveal variant="scale">
                <div className="relative overflow-hidden rounded-[2rem] bg-white px-8 py-12 text-center shadow-xl shadow-brand-orange/15 ring-1 ring-brand-orange/15 sm:px-12">
                    <div className="pointer-events-none absolute -top-16 left-1/2 size-56 -translate-x-1/2 rounded-full bg-brand-orange/10 blur-3xl" />
                    <div className="pointer-events-none absolute -bottom-20 right-10 size-40 rounded-full bg-brand-green/10 blur-3xl" />
                    <p className="relative text-xs font-semibold tracking-widest text-brand-green uppercase">
                        {content.eyebrow ?? 'Ready for something sweet?'}
                    </p>
                    <h2 className="relative mt-3 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                        {content.title ?? 'Scoop it. Sip it. Order it.'}
                    </h2>
                    <p className="relative mx-auto mt-3 max-w-xl text-brand-muted">
                        {content.description ??
                            'Explore ice cream, shakes, faluda, and kulfi — then place your order online.'}
                    </p>
                    <div className="relative mt-8 flex flex-wrap justify-center gap-3">
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
                </div>
            </ScrollReveal>
        </section>
    );
}
