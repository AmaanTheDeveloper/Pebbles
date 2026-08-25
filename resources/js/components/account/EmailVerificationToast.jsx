import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { useEmailVerificationReminder } from '../../contexts/EmailVerificationReminderContext';
import { isEmailVerified, resendVerificationEmail } from '../../lib/emailVerification';

const AUTO_DISMISS_MS = 10000;
const EXIT_ANIMATION_MS = 300;

export default function EmailVerificationToast() {
    const { user } = useAuth();
    const { reminder, isVisible, dismissReminder } = useEmailVerificationReminder();
    const [exiting, setExiting] = useState(false);
    const [sending, setSending] = useState(false);
    const [feedback, setFeedback] = useState(null);
    const timerRef = useRef(null);
    const exitTimerRef = useRef(null);
    const exitStartedRef = useRef(false);
    const dismissReminderRef = useRef(dismissReminder);

    useEffect(() => {
        dismissReminderRef.current = dismissReminder;
    }, [dismissReminder]);

    const clearTimers = () => {
        if (timerRef.current) {
            window.clearTimeout(timerRef.current);
            timerRef.current = null;
        }

        if (exitTimerRef.current) {
            window.clearTimeout(exitTimerRef.current);
            exitTimerRef.current = null;
        }
    };

    const beginExit = () => {
        if (exitStartedRef.current) {
            return;
        }

        exitStartedRef.current = true;
        clearTimers();
        setExiting(true);

        exitTimerRef.current = window.setTimeout(() => {
            dismissReminderRef.current();
        }, EXIT_ANIMATION_MS);
    };

    useEffect(() => {
        if (isEmailVerified(user) && isVisible) {
            dismissReminder();
        }
    }, [user, isVisible, dismissReminder]);

    useEffect(() => {
        if (!isVisible || !reminder) {
            exitStartedRef.current = false;
            setExiting(false);
            setFeedback(null);
            setSending(false);
            return undefined;
        }

        exitStartedRef.current = false;
        setExiting(false);
        setFeedback(null);
        clearTimers();

        timerRef.current = window.setTimeout(() => {
            beginExit();
        }, AUTO_DISMISS_MS);

        return () => {
            clearTimers();
        };
        // Timer intentionally depends on reminder identity (shownAt), not beginExit identity.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isVisible, reminder?.shownAt, reminder]);

    if (!isVisible || !reminder) {
        return null;
    }

    const handleDismiss = () => {
        beginExit();
    };

    const handleResend = async () => {
        if (sending) {
            return;
        }

        setSending(true);
        setFeedback(null);

        try {
            const result = await resendVerificationEmail();
            setFeedback({ type: 'success', message: result.message });
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
        <div
            className={['email-verify-toast', exiting ? 'is-exiting' : 'is-entering'].join(' ')}
            role="status"
            aria-live="polite"
            aria-atomic="true"
        >
            <div className="email-verify-toast-card">
                <div className="email-verify-toast-header">
                    <div className="email-verify-toast-icon" aria-hidden="true">
                        ✉
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="email-verify-toast-title">Verify Your Email</p>
                        <p className="email-verify-toast-copy">
                            We&apos;ve sent a verification link to your email address. Please verify your email
                            to secure your account and continue ordering.
                        </p>
                        {reminder.email ? (
                            <p className="email-verify-toast-email">
                                Verification email sent to:{' '}
                                <span className="font-semibold text-stone-800">{reminder.email}</span>
                            </p>
                        ) : null}
                    </div>
                    <button
                        type="button"
                        onClick={handleDismiss}
                        className="email-verify-toast-close"
                        aria-label="Dismiss verification reminder"
                    >
                        ×
                    </button>
                </div>

                {feedback ? (
                    <p
                        className={[
                            'email-verify-toast-feedback',
                            feedback.type === 'success' ? 'is-success' : 'is-error',
                        ].join(' ')}
                    >
                        {feedback.message}
                    </p>
                ) : null}

                <div className="email-verify-toast-actions">
                    <button
                        type="button"
                        onClick={handleResend}
                        disabled={sending}
                        className="btn-orange btn-shine email-verify-toast-btn disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {sending ? 'Sending…' : 'Resend Verification Email'}
                    </button>
                    <Link to="/verify-email" className="email-verify-toast-link" onClick={handleDismiss}>
                        Verification help
                    </Link>
                </div>

                <p className="email-verify-toast-hint">This reminder closes automatically in about 10 seconds.</p>
            </div>
        </div>
    );
}

export { AUTO_DISMISS_MS, EXIT_ANIMATION_MS };
