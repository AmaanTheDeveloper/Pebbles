import { useEffect, useState } from 'react';
import { fetchReviewSummary, fetchReviews } from '../api/reviews';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import ReviewFilters from '../components/storefront/reviews/ReviewFilters';
import ReviewSubmissionSection from '../components/storefront/reviews/ReviewSubmissionSection';
import ReviewsGrid from '../components/storefront/reviews/ReviewsGrid';
import ReviewsHero from '../components/storefront/reviews/ReviewsHero';
import ReviewsSummary from '../components/storefront/reviews/ReviewsSummary';
import { ScrollReveal } from '../hooks/useInView';

export default function ReviewsPage() {
    const [summary, setSummary] = useState(null);
    const [payload, setPayload] = useState({ data: [], meta: null, links: null });
    const [sort, setSort] = useState('newest');
    const [rating, setRating] = useState('');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [isFiltering, setIsFiltering] = useState(false);

    const load = async ({ nextSort = sort, nextRating = rating, nextPage = page, transition = false } = {}) => {
        if (transition) {
            setIsFiltering(true);
        } else {
            setLoading(true);
        }

        setError(null);

        try {
            const [summaryResponse, reviewsResponse] = await Promise.all([
                fetchReviewSummary(),
                fetchReviews({
                    sort: nextSort,
                    ...(nextRating ? { rating: nextRating } : {}),
                    page: nextPage,
                    per_page: 12,
                }),
            ]);

            setSummary(summaryResponse);
            setPayload({
                data: reviewsResponse.data ?? [],
                meta: reviewsResponse.meta ?? null,
                links: reviewsResponse.links ?? null,
            });
        } catch (err) {
            setError(err);
            setPayload({ data: [], meta: null, links: null });
        } finally {
            setLoading(false);
            if (transition) {
                window.setTimeout(() => setIsFiltering(false), 180);
            }
        }
    };

    useEffect(() => {
        load();
    }, []);

    const handleSortChange = (value) => {
        setSort(value);
        setPage(1);
        load({ nextSort: value, nextRating: rating, nextPage: 1, transition: true });
    };

    const handleRatingChange = (value) => {
        setRating(value);
        setPage(1);
        load({ nextSort: sort, nextRating: value, nextPage: 1, transition: true });
    };

    const handlePageChange = (nextPage) => {
        setPage(nextPage);
        load({ nextSort: sort, nextRating: rating, nextPage, transition: false });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    if (loading && !payload.data.length && !summary) {
        return (
            <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <LoadingState label="Loading reviews…" />
            </div>
        );
    }

    if (error && !payload.data.length) {
        return (
            <div className="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <ErrorState message={error.message || 'Unable to load reviews.'} onRetry={() => load()} />
            </div>
        );
    }

    const lastPage = payload.meta?.last_page ?? 1;

    return (
        <div>
            <ReviewsHero />

            <div className="-mt-8 space-y-10 pb-8 sm:pb-12">
                <ReviewsSummary summary={summary} />

                <section className="mx-auto max-w-6xl px-4 sm:px-6">
                    <ScrollReveal>
                        <ReviewFilters
                            sort={sort}
                            rating={rating}
                            onSortChange={handleSortChange}
                            onRatingChange={handleRatingChange}
                        />
                    </ScrollReveal>

                    <div className="mt-8">
                        <ReviewsGrid reviews={payload.data} isFiltering={isFiltering} />
                    </div>

                    {lastPage > 1 ? (
                        <div className="mt-8 flex flex-wrap items-center justify-center gap-2">
                            {Array.from({ length: lastPage }, (_, index) => {
                                const pageNumber = index + 1;
                                const isActive = pageNumber === page;

                                return (
                                    <button
                                        key={pageNumber}
                                        type="button"
                                        className={[
                                            'gallery-filter-chip min-w-10',
                                            isActive ? 'is-active' : '',
                                        ].join(' ')}
                                        onClick={() => handlePageChange(pageNumber)}
                                    >
                                        {pageNumber}
                                    </button>
                                );
                            })}
                        </div>
                    ) : null}
                </section>
            </div>

            <ReviewSubmissionSection />
        </div>
    );
}
