import { Link } from 'react-router-dom';
import { appName } from '../../../api/config';
import { ScrollReveal } from '../../../hooks/useInView';

export default function HomeStory({ imageUrl, imageAlt }) {
    return (
        <section className="hp-section relative overflow-hidden bg-brand-cream py-16 sm:py-20" id="story">
            <div className="pointer-events-none absolute -right-20 top-10 size-64 rounded-full bg-brand-green/10 blur-3xl" />
            <div className="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16">
                <ScrollReveal>
                    <div className="relative">
                        <div className="absolute -top-4 -left-4 h-24 w-24 rounded-full bg-brand-orange/20 blur-xl" />
                        <div className="absolute -right-3 -bottom-3 h-28 w-28 rounded-full bg-brand-green/20 blur-xl" />
                        <div className="hero-media relative overflow-hidden rounded-[1.75rem] shadow-xl ring-1 ring-brand-green/10">
                            {imageUrl ? (
                                <img
                                    src={imageUrl}
                                    alt={imageAlt || appName}
                                    className="aspect-[4/5] w-full object-cover"
                                    loading="lazy"
                                />
                            ) : (
                                <div className="flex aspect-[4/5] items-center justify-center bg-brand-green p-8 text-center text-white">
                                    <p className="font-display text-3xl font-semibold">{appName}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </ScrollReveal>
                <ScrollReveal delayClass="hp-scroll-delay-1">
                    <div className="space-y-5">
                        <p className="text-xs font-semibold tracking-widest text-brand-orange uppercase">Our story</p>
                        <h2 className="font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                            Made for cravings.
                            <span className="block text-brand-green">Perfect for every sip & scoop.</span>
                        </h2>
                        <div className="h-1 w-16 rounded-full bg-gradient-to-r from-brand-orange to-brand-green" />
                        <p className="text-base leading-relaxed text-brand-muted">
                            {appName} is all about shakes, ice cream, and refreshing Limca — delicious treats that are
                            easy to order for takeaway or delivery from a nearby branch.
                        </p>
                        <p className="text-base leading-relaxed text-brand-muted">
                            Explore the live menu, pick your favorites, and cool down with something you love.
                        </p>
                        <Link to="/menu" className="btn-green px-5 py-3 text-sm">
                            Explore the menu
                            <span className="btn-arrow" aria-hidden="true">
                                →
                            </span>
                        </Link>
                    </div>
                </ScrollReveal>
            </div>
        </section>
    );
}
