import { useState } from 'react';
import { submitContactMessage } from '../../../api/contact';
import { firstFieldError, normalizeApiError } from '../../../lib/errors';

const emptyForm = {
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
};

export default function ContactForm() {
    const [form, setForm] = useState(emptyForm);
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [success, setSuccess] = useState(null);
    const [submitError, setSubmitError] = useState(null);

    const updateField = (name, value) => {
        setForm((current) => ({ ...current, [name]: value }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();

        if (submitting) {
            return;
        }

        setSubmitting(true);
        setErrors({});
        setSubmitError(null);
        setSuccess(null);

        try {
            const response = await submitContactMessage({
                ...form,
                phone: form.phone || null,
            });
            setSuccess(response.message || 'Thanks! Your message has been sent successfully.');
            setForm(emptyForm);
        } catch (error) {
            const normalized = normalizeApiError(error);
            setErrors(normalized.errors ?? {});
            setSubmitError(normalized.message);
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
            {success ? (
                <p className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    {success}
                </p>
            ) : null}
            {submitError ? (
                <p className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {submitError}
                </p>
            ) : null}

            <div className="grid gap-4 sm:grid-cols-2">
                <label className="block space-y-1.5">
                    <span className="text-sm font-medium text-brand-ink">Name *</span>
                    <input
                        type="text"
                        name="name"
                        required
                        maxLength={120}
                        value={form.name}
                        onChange={(event) => updateField('name', event.target.value)}
                        className="contact-input"
                    />
                    {firstFieldError(errors, 'name') ? (
                        <span className="text-xs text-red-600">{firstFieldError(errors, 'name')}</span>
                    ) : null}
                </label>
                <label className="block space-y-1.5">
                    <span className="text-sm font-medium text-brand-ink">Email *</span>
                    <input
                        type="email"
                        name="email"
                        required
                        maxLength={255}
                        value={form.email}
                        onChange={(event) => updateField('email', event.target.value)}
                        className="contact-input"
                    />
                    {firstFieldError(errors, 'email') ? (
                        <span className="text-xs text-red-600">{firstFieldError(errors, 'email')}</span>
                    ) : null}
                </label>
            </div>

            <label className="block space-y-1.5">
                <span className="text-sm font-medium text-brand-ink">Phone</span>
                <input
                    type="tel"
                    name="phone"
                    maxLength={50}
                    value={form.phone}
                    onChange={(event) => updateField('phone', event.target.value)}
                    className="contact-input"
                />
            </label>

            <label className="block space-y-1.5">
                <span className="text-sm font-medium text-brand-ink">Subject *</span>
                <input
                    type="text"
                    name="subject"
                    required
                    maxLength={160}
                    value={form.subject}
                    onChange={(event) => updateField('subject', event.target.value)}
                    className="contact-input"
                />
                {firstFieldError(errors, 'subject') ? (
                    <span className="text-xs text-red-600">{firstFieldError(errors, 'subject')}</span>
                ) : null}
            </label>

            <label className="block space-y-1.5">
                <span className="text-sm font-medium text-brand-ink">Message *</span>
                <textarea
                    name="message"
                    required
                    rows={5}
                    maxLength={2000}
                    value={form.message}
                    onChange={(event) => updateField('message', event.target.value)}
                    className="contact-input min-h-32 resize-y"
                />
                {firstFieldError(errors, 'message') ? (
                    <span className="text-xs text-red-600">{firstFieldError(errors, 'message')}</span>
                ) : null}
            </label>

            <button type="submit" disabled={submitting} className="btn-orange btn-shine px-6 py-2.5 text-sm disabled:opacity-60">
                {submitting ? 'Sending…' : 'Send message'}
                <span className="btn-arrow" aria-hidden="true">
                    →
                </span>
            </button>
        </form>
    );
}
