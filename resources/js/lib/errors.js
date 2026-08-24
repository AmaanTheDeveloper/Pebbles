/**
 * Normalize Axios/Laravel validation and HTTP errors into a stable shape.
 *
 * @param {unknown} error
 * @returns {{ message: string, status: number|null, errors: Record<string, string[]> }}
 */
export function normalizeApiError(error) {
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
