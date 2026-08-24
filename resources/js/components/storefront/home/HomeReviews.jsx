import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchReviews } from '../../../api/reviews';
import { ScrollReveal, ScrollRevealItem } from '../../../hooks/useInView';
import LoadingState from '../LoadingState';
import ReviewCard from '../reviews/ReviewCard';
import ReviewSubmissionModal from '../reviews/ReviewSubmissionModal';

export default function HomeReviews({ content = {} }) {
    const [reviews, setReviews] = useState([]);
    const [loading, setLoading] = useState(true);
    const [modalOpen, setModalOpen] = useState(false);

    const loadReviews = async () => {
        setLoading(true);

        try {
            const featuredResponse = await fetchReviews({ featured: 1, per_page: content.review_limit ?? 6 });
            let items = featuredResponse.data ?? [];

            if (!items.length) {
                const latestResponse = await fetchReviews({ per_page: 3, sort: 'newest' });
                items = latestResponse.data ?? [];
            }

            setReviews(items.slice(0, content.review_limit ?? 6));
        } catch {
            setReviews([]);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadReviews();
    }, [content.review_limit]);

    return (
        <section className="hp-section mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="reviews">
            <ScrollReveal>
                <div className="mx-auto mb-10 max-w-2xl text-center">
                    <p className="hp-section-eyebrow text-xs font-semibold text-brand-green uppercase">
                        {content.eyebrow ?? 'What our customers say'}
                    </p>
                    <h2 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                        {content.title ?? 'Real Pebbles moments'}
                    </h2>
                    <p className="mt-3 text-brand-muted">
                        {content.description ?? 'Approved guest reviews from shakes, scoops, and visits at Pebbles.'}
                    </p>
                </div>
            </ScrollReveal>

            {loading ? (
                <div className="py-8">
                    <LoadingState label="Loading reviews…" />
                </div>
            ) : reviews.length ? (
                <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    {reviews.map((review, index) => (
                        <ScrollRevealItem key={review.id} index={index + 1} variant="scale" className="h-full">
                            <ReviewCard review={review} compact />
                        </ScrollRevealItem>
                    ))}
                </div>
            ) : (
                <ScrollReveal>
                    <div className="rounded-3xl bg-white/80 px-6 py-12 text-center ring-1 ring-brand-green/10">
                        <p className="font-display text-2xl font-semibold text-brand-ink">
                            {content.empty_title ?? 'Be the first to share your Pebbles experience'}
                        </p>
                        <p className="mt-2 text-sm text-brand-muted">
                            {content.empty_description ?? 'Tell us about your favorite shake, scoop, or visit.'}
                        </p>
                    </div>
                </ScrollReveal>
            )}

            <ScrollReveal delayClass="hp-scroll-delay-2">
                <div className="mt-10 flex flex-wrap justify-center gap-3">
                    <button type="button" className="btn-orange btn-shine px-6 py-3 text-sm" onClick={() => setModalOpen(true)}>
                        {content.share_cta_text ?? 'Share Your Experience'}
                    </button>
                    <Link to="/reviews" className="btn-ghost-green px-6 py-3 text-sm">
                        {content.view_all_cta_text ?? 'View All Reviews'}
                        <span className="btn-arrow" aria-hidden="true">
                            →
                        </span>
                    </Link>
                </div>
            </ScrollReveal>

            <ReviewSubmissionModal open={modalOpen} onClose={() => setModalOpen(false)} />
        </section>
    );
}
