import StarRating from './StarRating';

function formatReviewDate(value) {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

export default function ReviewCard({ review, compact = false }) {
    const dateLabel = formatReviewDate(review.created_at);

    return (
        <article
            className={[
                'review-card group lift-card lift-card-soft rounded-3xl bg-white ring-1 ring-brand-green/10',
                compact ? 'p-5' : 'p-6',
            ].join(' ')}
        >
            <div className="mb-4 flex items-start justify-between gap-3">
                <div className="flex items-center gap-3">
                    <div className="review-avatar flex size-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-green/15 to-brand-orange/15 font-display text-sm font-bold text-brand-green">
                        {review.initial || String(review.name || '?').slice(0, 1).toUpperCase()}
                    </div>
                    <div>
                        <h3 className="font-display text-lg font-semibold text-brand-ink transition group-hover:text-brand-green">
                            {review.name}
                        </h3>
                        {dateLabel ? <p className="text-xs text-brand-muted">{dateLabel}</p> : null}
                    </div>
                </div>
                {review.is_featured ? (
                    <span className="rounded-full bg-brand-orange/10 px-2 py-0.5 text-[10px] font-bold tracking-wide text-brand-orange uppercase">
                        Featured
                    </span>
                ) : null}
            </div>

            <StarRating rating={review.rating} className="mb-3" />
            <div className="review-accent mb-4 h-1 w-10 rounded-full bg-brand-orange" />
            <p className={`leading-relaxed text-brand-muted ${compact ? 'line-clamp-4 text-sm' : 'text-sm sm:text-base'}`}>
                &ldquo;{review.review}&rdquo;
            </p>
        </article>
    );
}
