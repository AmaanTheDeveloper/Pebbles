import { Link } from 'react-router-dom';
import { appName } from '../../../api/config';

export default function AboutHero({ imageUrl, imageAlt }) {
    return (
        <section className="relative overflow-hidden border-b border-brand-green/10">
            <div className="pointer-events-none absolute inset-0">
                <div className="hp-float absolute -top-16 -left-10 size-56 rounded-full bg-brand-green/15 blur-2xl" />
                <div className="hp-float-slow absolute top-20 right-8 size-44 rounded-full bg-brand-orange/20 blur-2xl" />
            </div>

            <div className="relative mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-2 lg:gap-14 lg:py-24">
                <div className="space-y-6">
                    <p className="hp-reveal hp-section-eyebrow inline-flex items-center gap-2 rounded-full border border-brand-green/15 bg-white/70 px-3 py-1.5 text-xs font-semibold text-brand-green uppercase shadow-sm backdrop-blur-sm">
                        <span className="size-1.5 rounded-full bg-brand-orange" />
                        Our story
                    </p>
                    <h1 className="hp-reveal hp-reveal-delay-1 font-display text-4xl leading-[1.08] font-semibold tracking-tight text-brand-ink sm:text-5xl lg:text-6xl">
                        More Than
                        <span className="block bg-gradient-to-r from-brand-orange via-[#fb923c] to-[#ea580c] bg-clip-text text-transparent">
                            Just a Treat
                        </span>
                    </h1>
                    <p className="hp-reveal hp-reveal-delay-2 max-w-lg text-base leading-relaxed text-brand-muted sm:text-lg">
                        At {appName}, we believe every sip, scoop, and smile should feel special — premium desserts and
                        refreshing drinks made for real moments.
                    </p>
                    <div className="hp-reveal hp-reveal-delay-3 flex flex-wrap gap-3">
                        <Link to="/menu" className="btn-orange btn-shine px-6 py-3 text-sm">
                            Explore Menu
                            <span className="btn-arrow" aria-hidden="true">
                                →
                            </span>
                        </Link>
                        <Link to="/order" className="btn-ghost-green px-6 py-3 text-sm">
                            Order Online
                            <span className="btn-arrow" aria-hidden="true">
                                →
                            </span>
                        </Link>
                    </div>
                </div>

                <div className="hp-reveal hp-reveal-delay-2 relative mx-auto w-full max-w-md lg:max-w-none">
                    <div className="hero-glow-pulse absolute -inset-3 rounded-[2rem] bg-gradient-to-br from-brand-green/25 via-transparent to-brand-orange/30 blur-md" />
                    <div className="hero-media hero-image-float relative overflow-hidden rounded-[1.75rem] bg-white shadow-2xl shadow-brand-green/20 ring-1 ring-brand-green/10">
                        {imageUrl ? (
                            <img
                                src={imageUrl}
                                alt={imageAlt || `${appName} treats`}
                                className="aspect-[4/5] w-full object-cover sm:aspect-square"
                            />
                        ) : (
                            <div className="flex aspect-[4/5] flex-col items-center justify-center gap-3 bg-gradient-to-br from-brand-green to-brand-green-light p-8 text-center text-white sm:aspect-square">
                                <p className="font-display text-3xl font-semibold">{appName}</p>
                                <p className="text-sm text-white/85">Shakes, scoops & refreshing sips.</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
