import { appName } from '../../../api/config';
import { ScrollReveal } from '../../../hooks/useInView';

export default function AboutBrandStatement() {
    return (
        <section className="hp-section mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="brand-statement">
            <ScrollReveal variant="scale">
                <div className="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-brand-green via-brand-green to-brand-green-light px-8 py-14 text-center text-white shadow-2xl shadow-brand-green/25 sm:px-12 sm:py-16">
                    <div className="hp-float pointer-events-none absolute -top-10 -right-8 size-40 rounded-full bg-brand-orange/30 blur-2xl" />
                    <div className="pointer-events-none absolute bottom-0 left-10 size-48 rounded-full bg-white/10 blur-2xl" />
                    <p className="relative text-xs font-semibold tracking-widest text-brand-orange-soft uppercase">
                        {appName}
                    </p>
                    <h2 className="relative mt-4 font-display text-3xl font-semibold sm:text-4xl lg:text-5xl">
                        Good Taste. Good Vibes.
                        <span className="mt-2 block text-white/95">Good Memories.</span>
                    </h2>
                    <p className="relative mx-auto mt-5 max-w-xl text-sm leading-relaxed text-white/85 sm:text-base">
                        Whether it is a thick shake, a cold scoop, or a refreshing Limca — we are here to make the
                        moment feel a little more special.
                    </p>
                </div>
            </ScrollReveal>
        </section>
    );
}
