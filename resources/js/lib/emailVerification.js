import api from '../api/client';
import { normalizeApiError } from './errors';

/**
 * @param {{ email_verified_at?: string|null, role?: string|null }|null|undefined} user
 */
export function isEmailVerified(user) {
    return Boolean(user?.email_verified_at);
}

/**
 * @param {{ email_verified_at?: string|null, role?: string|null }|null|undefined} user
 */
export function shouldPromptEmailVerification(user) {
    if (!user) {
        return false;
    }

    if (user.role === 'manager' || user.role === 'super_admin') {
        return false;
    }

    return !isEmailVerified(user);
}

/**
 * Call the existing Laravel verification-notification endpoint.
 *
 * @returns {Promise<{ alreadyVerified: boolean, message: string }>}
 */
export async function resendVerificationEmail() {
    try {
        const response = await api.post('/api/email/verification-notification');

        if (response.status === 204) {
            return {
                alreadyVerified: true,
                message: 'Your email is already verified.',
            };
        }

        const status = response.data?.status;

        return {
            alreadyVerified: false,
            message:
                status === 'verification-link-sent'
                    ? 'Verification email sent successfully. Please check your inbox.'
                    : 'Verification email sent.',
        };
    } catch (error) {
        throw normalizeVerificationResendError(error);
    }
}

/**
 * @param {unknown} error
 * @returns {{ message: string, status: number|null, errors: Record<string, string[]>, throttled: boolean }}
 */
export function normalizeVerificationResendError(error) {
    const normalized = normalizeApiError(error);
    const throttled = normalized.status === 429;

    return {
        ...normalized,
        throttled,
        message: throttled
            ? 'Please wait a moment before requesting another verification email.'
            : normalized.message || 'Unable to send verification email. Please try again.',
    };
}
