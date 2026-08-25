/**
 * Normalize Axios/Laravel validation and HTTP errors into a stable shape.
 *
 * @param {unknown} error
 * @returns {{ message: string, status: number|null, errors: Record<string, string[]> }}
 */
export function normalizeApiError(error) {
    if (isNormalizedApiError(error)) {
        return error;
    }

    if (!error || typeof error !== 'object' || !('isAxiosError' in error)) {
        return {
            message: 'An unexpected error occurred.',
            status: null,
            errors: {},
        };
    }

    const axiosError = error;
    const status = axiosError.response?.status ?? null;
    const data = axiosError.response?.data;

    if (data && typeof data === 'object') {
        const message =
            typeof data.message === 'string'
                ? data.message
                : 'Something went wrong. Please try again.';

        const errors =
            data.errors && typeof data.errors === 'object' ? data.errors : {};

        return { message, status, errors };
    }

    if (axiosError.request && !axiosError.response) {
        return {
            message: 'Unable to reach the server. Check your connection.',
            status: null,
            errors: {},
        };
    }

    return {
        message: axiosError.message || 'An unexpected error occurred.',
        status,
        errors: {},
    };
}

/**
 * @param {unknown} value
 * @returns {value is { message: string, status: number|null, errors: Record<string, string[]> }}
 */
function isNormalizedApiError(value) {
    return (
        Boolean(value) &&
        typeof value === 'object' &&
        !('isAxiosError' in value) &&
        'message' in value &&
        typeof value.message === 'string' &&
        'status' in value &&
        'errors' in value &&
        typeof value.errors === 'object' &&
        value.errors !== null
    );
}

/**
 * Return the first validation message for a field, if present.
 *
 * @param {Record<string, string[]>} errors
 * @param {string} field
 * @returns {string|null}
 */
export function firstFieldError(errors, field) {
    const messages = errors?.[field];

    if (!Array.isArray(messages) || messages.length === 0) {
        return null;
    }

    return messages[0];
}

const COUPON_TECHNICAL_FALLBACK = 'Unable to verify coupon right now. Please try again.';

/**
 * Extract a customer-safe coupon apply/remove message from Axios or already-normalized errors.
 *
 * @param {unknown} error
 * @param {'apply'|'remove'} [action='apply']
 * @returns {string}
 */
export function extractCouponError(error, action = 'apply') {
    const normalized = normalizeApiError(error);
    const status = normalized.status;
    const fieldMessage =
        firstFieldError(normalized.errors, 'coupon_code') ||
        firstFieldError(normalized.errors, 'code') ||
        firstFieldError(normalized.errors, 'coupon');

    if (status === 422 || status === 409) {
        return fieldMessage || (isGenericLaravelMessage(normalized.message) ? COUPON_TECHNICAL_FALLBACK : normalized.message);
    }

    if (status === 401 || status === 403) {
        return normalized.message || COUPON_TECHNICAL_FALLBACK;
    }

    if (status !== null && status >= 500) {
        return COUPON_TECHNICAL_FALLBACK;
    }

    if (status === null) {
        return COUPON_TECHNICAL_FALLBACK;
    }

    if (fieldMessage) {
        return fieldMessage;
    }

    if (normalized.message && !isGenericUnexpectedMessage(normalized.message) && !isGenericLaravelMessage(normalized.message)) {
        return normalized.message;
    }

    return action === 'remove'
        ? 'Unable to remove coupon. Please try again.'
        : COUPON_TECHNICAL_FALLBACK;
}

/**
 * @param {string} message
 * @returns {boolean}
 */
function isGenericUnexpectedMessage(message) {
    return message === 'An unexpected error occurred.' || message === 'Something went wrong. Please try again.';
}

/**
 * @param {string} message
 * @returns {boolean}
 */
function isGenericLaravelMessage(message) {
    return message === 'The given data was invalid.';
}
