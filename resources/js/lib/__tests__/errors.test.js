import { describe, expect, test } from 'vitest';
import { firstFieldError, normalizeApiError } from '../errors';

describe('normalizeApiError', () => {
    test('returns generic shape for non-axios errors', () => {
        expect(normalizeApiError(new Error('boom'))).toEqual({
            message: 'An unexpected error occurred.',
            status: null,
            errors: {},
        });
    });

    test('passes through already-normalized errors', () => {
        const normalized = {
            message: 'Saved',
            status: 422,
            errors: { email: ['Taken'] },
        };

        expect(normalizeApiError(normalized)).toBe(normalized);
    });

    test('extracts Laravel validation payload from axios errors', () => {
        const error = {
            isAxiosError: true,
            response: {
                status: 422,
                data: {
                    message: 'The given data was invalid.',
                    errors: { email: ['The email has already been taken.'] },
                },
            },
        };

        expect(normalizeApiError(error)).toEqual({
            message: 'The given data was invalid.',
            status: 422,
            errors: { email: ['The email has already been taken.'] },
        });
    });

    test('reports network failures when request exists without response', () => {
        const error = {
            isAxiosError: true,
            request: {},
            message: 'Network Error',
        };

        expect(normalizeApiError(error)).toEqual({
            message: 'Unable to reach the server. Check your connection.',
            status: null,
            errors: {},
        });
    });

    test('firstFieldError returns the first validation message', () => {
        expect(firstFieldError({ email: ['A', 'B'] }, 'email')).toBe('A');
        expect(firstFieldError({}, 'email')).toBeNull();
    });
});
