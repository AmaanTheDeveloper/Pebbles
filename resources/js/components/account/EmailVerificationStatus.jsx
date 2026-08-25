import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { isEmailVerified, resendVerificationEmail } from '../../lib/emailVerification';

export default function EmailVerificationStatus() {
    const { user, refreshUser } = useAuth();
    const [sending, setSending] = useState(false);
    const [feedback, setFeedback] = useState(null);

    if (!user) {
        return null;
    }

    const verified = isEmailVerified(user);

    const handleResend = async () => {
        if (sending) {
            return;
        }

        setSending(true);
        setFeedback(null);

        try {
            const result = await resendVerificationEmail();
            setFeedback({ type: 'success', message: result.message });

            if (result.alreadyVerified) {
                await refreshUser();
            }
        } catch (error) {
            setFeedback({
                type: 'error',
                message: error?.message || 'Unable to send verification email.',
            });
        } finally {
            setSending(false);
        }
    };

    return (
        <section
            className={[
                'rounded-2xl p-5 ring-1',
                verified
                    ? 'bg-emerald-50/80 ring-emerald-200'
                    : 'bg-amber-50/90 ring-amber-200',
            ].join(' ')}
            aria-labelledby="email-verification-heading"
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0 space-y-1">
                    <p className="text-xs font-semibold tracking-wide text-stone-500 uppercase">
                        Email address
                    </p>
                    <p id="email-verification-heading" className="truncate text-sm font-semibold text-stone-900">
                        {user.email}
                    </p>
                </div>
                {verified ? (
                    <p className="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-900 ring-1 ring-emerald-200">
                        <span aria-hidden="true">✓</span>
                        Email Verified
                    </p>
                ) : (
                    <p className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-950 ring-1 ring-amber-300">
                        <span aria-hidden="true">!</span>
                        Not verified
                    </p>
                )}
            </div>

            {verified ? (
                <p className="mt-3 text-sm text-emerald-900/80">
                    Your email is verified. You can place orders and manage your account securely.
                </p>
            ) : (
                <div className="mt-3 space-y-3">
                    <p className="text-sm font-medium text-amber-950">
                        Your email address is not verified.
                    </p>
                    <p className="text-sm text-amber-950/80">
                        Please verify your email address to secure your account and continue using all customer
                        features.
                    </p>
                    {feedback ? (
                        <p
                            className={[
                                'rounded-xl px-3 py-2 text-sm',
                                feedback.type === 'success'
                                    ? 'bg-emerald-50 text-emerald-900 ring-1 ring-emerald-200'
                                    : 'bg-red-50 text-red-800 ring-1 ring-red-200',
                            ].join(' ')}
                            role="status"
                        >
                            {feedback.message}
                        </p>
                    ) : null}
                    <div className="flex flex-wrap gap-3">
                        <button
                            type="button"
                            onClick={handleResend}
                            disabled={sending}
                            className="rounded-xl bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-stone-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {sending ? 'Sending…' : 'Resend Verification Email'}
                        </button>
                        <Link
                            to="/verify-email"
                            className="inline-flex items-center text-sm font-semibold text-amber-950 underline-offset-2 hover:underline"
                        >
                            Open verification page
                        </Link>
                    </div>
                </div>
            )}
        </section>
    );
}
