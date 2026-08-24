const PREPARATION_HEADING_CYCLE_MS = 6000;

/**
 * Word-by-word reveal for preparation headings (confirmed / preparing).
 * Reserves full phrase width to prevent layout shift.
 */
export default function AnimatedPreparationHeading({ heading, animated = false, className = '' }) {
    const words = heading.trim().split(/\s+/);
    const wordCount = words.length;

    if (!animated) {
        return (
            <h2
                className={[
                    'font-display text-xl font-semibold tracking-tight text-brand-ink uppercase sm:text-2xl',
                    className,
                ]
                    .filter(Boolean)
                    .join(' ')}
            >
                {heading}
            </h2>
        );
    }

    return (
        <h2
            className={[
                'preparation-heading font-display text-xl font-semibold tracking-tight text-brand-ink uppercase sm:text-2xl',
                'is-animated',
                className,
            ]
                .filter(Boolean)
                .join(' ')}
            data-word-count={wordCount}
            style={{ '--prep-cycle': `${PREPARATION_HEADING_CYCLE_MS}ms` }}
        >
            <span className="preparation-heading-stack">
                <span className="preparation-heading-ghost" aria-hidden="true">
                    {heading}
                </span>
                <span className="preparation-heading-words">
                    {words.map((word, index) => (
                        <span
                            key={`${word}-${index}`}
                            className="preparation-heading-word"
                            style={{ '--word-i': index }}
                        >
                            {word}
                            {index < words.length - 1 ? '\u00a0' : ''}
                        </span>
                    ))}
                </span>
            </span>
        </h2>
    );
}
