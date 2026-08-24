export default function MenuBookHero() {
    return (
        <section className="relative overflow-hidden border-b border-brand-green/10">
            <div className="pointer-events-none absolute inset-0">
                <div className="hp-float absolute -top-16 -left-10 size-56 rounded-full bg-brand-green/15 blur-2xl" />
                <div className="hp-float-slow absolute top-20 right-8 size-44 rounded-full bg-brand-orange/20 blur-2xl" />
            </div>

            <div className="relative mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20 lg:py-24">
                <div className="mx-auto max-w-3xl text-center">
                    <p className="hp-reveal hp-section-eyebrow inline-flex items-center gap-2 rounded-full border border-brand-green/15 bg-white/70 px-3 py-1.5 text-xs font-semibold text-brand-green uppercase shadow-sm backdrop-blur-sm">
                        <span className="size-1.5 rounded-full bg-brand-orange" />
                        Our menu
                    </p>
                    <h1 className="hp-reveal hp-reveal-delay-1 mt-6 font-display text-4xl leading-[1.08] font-semibold tracking-tight text-brand-ink sm:text-5xl lg:text-6xl">
                        The Pebbles Menu
                    </h1>
                    <p className="hp-reveal hp-reveal-delay-2 mx-auto mt-5 max-w-2xl text-base leading-relaxed text-brand-muted sm:text-lg">
                        Browse our restaurant menu and explore everything Pebbles has to offer.
                    </p>
                </div>
            </div>
        </section>
    );
}
