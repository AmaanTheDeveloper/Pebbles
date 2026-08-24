import { Link } from 'react-router-dom';
import { ScrollReveal } from '../../../hooks/useInView';

export default function GalleryCta() {
    return (
        <section className="hp-section mx-auto max-w-6xl px-4 pb-16 sm:px-6 sm:pb-20" id="gallery-cta">
            <ScrollReveal variant="scale">
                <div className="relative overflow-hidden rounded-[2rem] bg-white px-8 py-12 text-center shadow-xl shadow-brand-orange/15 ring-1 ring-brand-orange/15 sm:px-12">
                    <div className="pointer-events-none absolute -top-16 left-1/2 size-56 -translate-x-1/2 rounded-full bg-brand-orange/10 blur-3xl" />
                    <p className="relative text-xs font-semibold tracking-widest text-brand-green uppercase">
                        Visit Pebbles
                    </p>
                    <h2 className="relative mt-3 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                        Come Experience Pebbles Yourself
                    </h2>
                    <p className="relative mx-auto mt-3 max-w-xl text-brand-muted">
                        Browse the menu, discover current offers, or order online for takeaway and delivery.
                    </p>
                    <div className="relative mt-8 flex flex-wrap justify-center gap-3">
                        <Link to="/menu" className="btn-orange btn-shine px-6 py-3 text-sm">
                            Explore Menu
                            <span className="btn-arrow" aria-hidden="true">
                                →
                            </span>
                        </Link>
                        <Link to="/offers" className="btn-ghost-green px-6 py-3 text-sm">
                            View Offers
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
