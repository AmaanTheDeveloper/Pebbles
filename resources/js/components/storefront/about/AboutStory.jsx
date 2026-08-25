import { Link } from 'react-router-dom';
import { appName } from '../../../api/config';
import { ScrollReveal } from '../../../hooks/useInView';

export default function AboutStory({ imageUrl, imageAlt, content }) {
    const story = content ?? {};
    const paragraphs = Array.isArray(story.paragraphs) ? story.paragraphs : [];

    return (
        <section className="hp-section hp-section-divider relative overflow-hidden bg-brand-cream/80 py-16 sm:py-20" id="our-story">
            <div className="pointer-events-none absolute -right-20 top-10 size-64 rounded-full bg-brand-green/10 blur-3xl" />
            <div className="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16">
                <ScrollReveal>
                    <div className="relative">
                        <div className="hero-media relative overflow-hidden rounded-[1.75rem] shadow-xl ring-1 ring-brand-green/10">
                            {imageUrl ? (
                                <img
                                    src={imageUrl}
                                    alt={imageAlt || `${appName} story`}
                                    className="aspect-[4/5] w-full object-cover"
                                    loading="lazy"
                                />
                            ) : (
                                <div className="flex aspect-[4/5] items-center justify-center bg-gradient-to-br from-brand-green to-brand-green-light p-8 text-center text-white">
                                    <p className="font-display text-3xl font-semibold">{appName}</p>
                                </div>
                            )}
                        </div>
                    </div>
                </ScrollReveal>

                <ScrollReveal delayClass="hp-scroll-delay-1">
                    <div className="hp-section-head space-y-5">
                        <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">{story.eyebrow}</p>
                        <h2 className="font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                            {story.title}
                            <span className="block text-brand-green"> {story.title_accent}</span>
                        </h2>
                        <div className="h-1 w-16 rounded-full bg-gradient-to-r from-brand-orange to-brand-green" />
                        {paragraphs.map((paragraph) => (
                            <p key={paragraph} className="text-base leading-relaxed text-brand-muted">
                                {paragraph}
                            </p>
                        ))}
                        <Link to="/menu" className="btn-green px-5 py-3 text-sm">
                            See what we serve
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
