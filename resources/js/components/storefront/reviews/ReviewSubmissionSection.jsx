import { ScrollReveal } from '../../../hooks/useInView';
import ReviewForm from './ReviewForm';

export default function ReviewSubmissionSection() {
    return (
        <section className="hp-section mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20" id="write-review">
            <ScrollReveal>
                <div className="grid gap-8 overflow-hidden rounded-[2rem] bg-gradient-to-br from-brand-cream via-white to-brand-cream p-6 ring-1 ring-brand-green/10 sm:p-8 lg:grid-cols-2 lg:gap-10 lg:p-10">
                    <div className="hp-section-head space-y-4">
                        <p className="hp-section-eyebrow text-xs font-semibold text-brand-orange uppercase">
                            Share your story
                        </p>
                        <h2 className="font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                            Share Your Pebbles Experience
                        </h2>
                        <p className="text-brand-muted">
                            Tried something you loved? Tell us about it. Approved reviews appear here and on our
                            homepage.
                        </p>
                        <ul className="space-y-2 text-sm text-brand-muted">
                            <li>★ Honest feedback about shakes, scoops, and visits</li>
                            <li>★ Reviews are moderated before publishing</li>
                            <li>★ One submission per hour per email</li>
                        </ul>
                    </div>

                    <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-green/10 sm:p-6">
                        <ReviewForm formId="reviews-page-form" />
                    </div>
                </div>
            </ScrollReveal>
        </section>
    );
}
