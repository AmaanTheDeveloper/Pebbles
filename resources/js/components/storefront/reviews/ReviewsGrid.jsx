import EmptyState from '../EmptyState';
import ReviewCard from './ReviewCard';
import { ScrollRevealItem } from '../../../hooks/useInView';

export default function ReviewsGrid({ reviews, isFiltering }) {
    if (!reviews.length) {
        return (
            <div className="rounded-3xl bg-white/80 px-6 py-16 ring-1 ring-brand-green/10">
                <EmptyState
                    title="No reviews in this view"
                    description="No reviews match these filters yet."
                />
            </div>
        );
    }

    return (
        <div
            className={[
                'grid gap-5 md:grid-cols-2 xl:grid-cols-3',
                isFiltering ? 'reviews-grid-filtering' : 'reviews-grid-animate-in',
            ].join(' ')}
        >
            {reviews.map((review, index) => (
                <ScrollRevealItem key={review.id} index={index + 1} variant="scale" className="h-full">
                    <ReviewCard review={review} />
                </ScrollRevealItem>
            ))}
        </div>
    );
}
