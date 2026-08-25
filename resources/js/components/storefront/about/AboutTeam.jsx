import { ScrollReveal, ScrollRevealItem } from '../../../hooks/useInView';

export default function AboutTeam({ content }) {
    const team = content ?? {};
    const roles = Array.isArray(team.roles) ? team.roles : [];

    return (
        <section className="hp-section hp-section-divider mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="team">
            <ScrollReveal>
                <div className="hp-section-head mx-auto mb-10 max-w-2xl text-center">
                    <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">{team.eyebrow}</p>
                    <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">{team.title}</h2>
                    <p className="mt-3 text-brand-muted">{team.subtitle}</p>
                </div>
            </ScrollReveal>

            <div className="grid gap-5 md:grid-cols-3">
                {roles.map((member, index) => (
                    <ScrollRevealItem key={member.key || member.role} index={index + 1} variant="scale" className="h-full">
                        <article className="about-team-card group lift-card lift-card-soft flex h-full flex-col overflow-hidden rounded-3xl bg-white ring-1 ring-brand-green/10">
                            <div className={`about-team-media flex aspect-[4/3] items-center justify-center bg-gradient-to-br ${member.accent}`}>
                                <div className="review-avatar flex size-20 items-center justify-center rounded-full bg-white/90 font-display text-2xl font-bold text-brand-green shadow-lg ring-4 ring-white/60">
                                    {(member.role || '?').slice(0, 1)}
                                </div>
                            </div>
                            <div className="flex flex-1 flex-col p-6">
                                <h3 className="font-display text-xl font-semibold text-brand-ink transition group-hover:text-brand-green">
                                    {member.role}
                                </h3>
                                <p className="mt-2 text-sm leading-relaxed text-brand-muted">{member.description}</p>
                            </div>
                        </article>
                    </ScrollRevealItem>
                ))}
            </div>
        </section>
    );
}
