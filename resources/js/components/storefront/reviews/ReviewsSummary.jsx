import StarRating from './StarRating';

export default function ReviewsSummary({ summary }) {
    const totalReviews = summary?.total_reviews ?? 0;
    const averageRating = summary?.average_rating;
    const distribution = summary?.distribution ?? { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };
    const maxCount = Math.max(...Object.values(distribution), 1);

    return (
        <section className="mx-auto max-w-6xl px-4 sm:px-6">
            <div className="rounded-[1.75rem] bg-white p-6 shadow-lg shadow-brand-green/10 ring-1 ring-brand-green/10 sm:p-8">
                {totalReviews === 0 ? (
                    <div className="text-center">
                        <p className="font-display text-2xl font-semibold text-brand-ink">No reviews yet</p>
                        <p className="mt-2 text-sm text-brand-muted">
                            Be the first to share your Pebbles experience below.
                        </p>
                    </div>
                ) : (
                    <div className="grid gap-8 lg:grid-cols-[minmax(0,16rem)_1fr] lg:items-center">
                        <div className="text-center lg:text-left">
                            <p className="text-xs font-semibold tracking-widest text-brand-green uppercase">
                                Overall rating
                            </p>
                            <p className="mt-2 font-display text-5xl font-semibold text-brand-ink">{averageRating ?? '—'}</p>
                            <div className="mt-2 flex justify-center lg:justify-start">
                                <StarRating rating={Math.round(averageRating ?? 0)} size="lg" />
                            </div>
                            <p className="mt-3 text-sm text-brand-muted">
                                {totalReviews} review{totalReviews === 1 ? '' : 's'}
                            </p>
                        </div>

                        <div className="space-y-2">
                            {[5, 4, 3, 2, 1].map((stars) => {
                                const count = distribution[stars] ?? 0;
                                const width = `${Math.round((count / maxCount) * 100)}%`;

                                return (
                                    <div key={stars} className="grid grid-cols-[3.5rem_1fr_2.5rem] items-center gap-3 text-sm">
                                        <span className="font-semibold text-brand-ink">{stars} stars</span>
                                        <div className="h-2 overflow-hidden rounded-full bg-brand-cream ring-1 ring-brand-green/10">
                                            <div
                                                className="h-full rounded-full bg-gradient-to-r from-brand-orange to-[#ea580c] transition-all duration-500"
                                                style={{ width }}
                                            />
                                        </div>
                                        <span className="text-right text-brand-muted">{count}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}
