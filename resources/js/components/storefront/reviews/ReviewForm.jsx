import { useState } from 'react';
import { submitReview } from '../../../api/reviews';
import { firstFieldError, normalizeApiError } from '../../../lib/errors';
import StarRatingInput from './StarRatingInput';

const initialForm = {
    name: '',
    email: '',
    rating: 0,
    review: '',
};

export default function ReviewForm({ onSuccess, formId = 'review-form' }) {
    const [form, setForm] = useState(initialForm);
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [successMessage, setSuccessMessage] = useState('');

    const handleChange = (field) => (event) => {
        setForm((current) => ({ ...current, [field]: event.target.value }));
        setErrors((current) => ({ ...current, [field]: undefined }));
        setSuccessMessage('');
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        if (submitting) {
            return;
        }

        if (!form.rating) {
            setErrors({ rating: ['Please select a rating.'] });
            return;
        }

        setSubmitting(true);
        setErrors({});
        setSuccessMessage('');

        try {
            const response = await submitReview({
                name: form.name.trim(),
                email: form.email.trim(),
                rating: form.rating,
                review: form.review.trim(),
            });

            setSuccessMessage(
                response.message ||
                    'Thank you! Your review has been submitted and is awaiting approval.',
            );
            setForm(initialForm);
            onSuccess?.(response);
        } catch (error) {
            const normalized = normalizeApiError(error);
            setErrors(normalized.errors ?? {});

            if (!Object.keys(normalized.errors ?? {}).length) {
                setErrors({ form: normalized.message });
            }
        } finally {
            setSubmitting(false);
        }
    };

    if (successMessage) {
        return (
            <div className="rounded-2xl border border-brand-green/15 bg-brand-green/5 px-5 py-6 text-center">
                <p className="font-display text-xl font-semibold text-brand-green">Thank you!</p>
                <p className="mt-2 text-sm leading-relaxed text-brand-muted">{successMessage}</p>
                <button
                    type="button"
                    className="btn-ghost-green mt-5 px-4 py-2 text-sm"
                    onClick={() => {
                        setSuccessMessage('');
                        setForm(initialForm);
                    }}
                >
                    Submit another review
                </button>
            </div>
        );
    }

    return (
        <form id={formId} className="space-y-4" onSubmit={handleSubmit} noValidate>
            {errors.form ? (
                <p className="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">
                    {errors.form}
                </p>
            ) : null}

            <div className="grid gap-4 sm:grid-cols-2">
                <div>
                    <label htmlFor={`${formId}-name`} className="mb-1 block text-sm font-semibold text-brand-ink">
                        Your name
                    </label>
                    <input
                        id={`${formId}-name`}
                        type="text"
                        value={form.name}
                        onChange={handleChange('name')}
                        className="w-full rounded-xl border border-brand-green/15 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-green focus:ring-2 focus:ring-brand-green/15"
                        autoComplete="name"
                        required
                    />
                    {firstFieldError(errors, 'name') ? (
                        <p className="mt-1 text-sm text-red-600">{firstFieldError(errors, 'name')}</p>
                    ) : null}
                </div>

                <div>
                    <label htmlFor={`${formId}-email`} className="mb-1 block text-sm font-semibold text-brand-ink">
                        Email
                    </label>
                    <input
                        id={`${formId}-email`}
                        type="email"
                        value={form.email}
                        onChange={handleChange('email')}
                        className="w-full rounded-xl border border-brand-green/15 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-green focus:ring-2 focus:ring-brand-green/15"
                        autoComplete="email"
                        required
                    />
                    {firstFieldError(errors, 'email') ? (
                        <p className="mt-1 text-sm text-red-600">{firstFieldError(errors, 'email')}</p>
                    ) : null}
                </div>
            </div>

            <div>
                <span className="mb-2 block text-sm font-semibold text-brand-ink">Your rating</span>
                <StarRatingInput
                    id={`${formId}-rating`}
                    value={form.rating}
                    onChange={(rating) => {
                        setForm((current) => ({ ...current, rating }));
                        setErrors((current) => ({ ...current, rating: undefined }));
                    }}
                    error={firstFieldError(errors, 'rating')}
                />
            </div>

            <div>
                <label htmlFor={`${formId}-review`} className="mb-1 block text-sm font-semibold text-brand-ink">
                    Your review
                </label>
                <textarea
                    id={`${formId}-review`}
                    value={form.review}
                    onChange={handleChange('review')}
                    rows={5}
                    maxLength={1000}
                    className="w-full rounded-xl border border-brand-green/15 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand-green focus:ring-2 focus:ring-brand-green/15"
                    placeholder="Tell us about your favorite shake, scoop, or visit…"
                    required
                />
                <div className="mt-1 flex items-center justify-between gap-3">
                    {firstFieldError(errors, 'review') ? (
                        <p className="text-sm text-red-600">{firstFieldError(errors, 'review')}</p>
                    ) : (
                        <span className="text-xs text-brand-muted">10–1000 characters</span>
                    )}
                    <span className="text-xs text-brand-muted">{form.review.length}/1000</span>
                </div>
            </div>

            <button type="submit" className="btn-orange btn-shine px-6 py-3 text-sm disabled:cursor-not-allowed disabled:opacity-70" disabled={submitting}>
                {submitting ? 'Submitting…' : 'Submit Review'}
            </button>
        </form>
    );
}
