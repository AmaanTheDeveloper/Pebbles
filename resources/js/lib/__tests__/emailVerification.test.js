import { describe, expect, test, vi } from 'vitest';
import {
    isEmailVerified,
    normalizeVerificationResendError,
    shouldPromptEmailVerification,
} from '../emailVerification';

describe('emailVerification helpers', () => {
    test('isEmailVerified reads email_verified_at', () => {
        expect(isEmailVerified(null)).toBe(false);
        expect(isEmailVerified({ email_verified_at: null })).toBe(false);
        expect(isEmailVerified({ email_verified_at: '2026-08-25T10:00:00Z' })).toBe(true);
    });

    test('shouldPromptEmailVerification only for unverified customers', () => {
        expect(shouldPromptEmailVerification(null)).toBe(false);
        expect(
            shouldPromptEmailVerification({
                role: 'user',
                email_verified_at: null,
            }),
        ).toBe(true);
        expect(
            shouldPromptEmailVerification({
                role: 'user',
                email_verified_at: '2026-08-25T10:00:00Z',
            }),
        ).toBe(false);
        expect(
            shouldPromptEmailVerification({
                role: 'manager',
                email_verified_at: null,
            }),
        ).toBe(false);
        expect(
            shouldPromptEmailVerification({
                role: 'super_admin',
                email_verified_at: null,
            }),
        ).toBe(false);
    });

    test('normalizeVerificationResendError maps 429 to a friendly wait message', () => {
        const result = normalizeVerificationResendError({
            isAxiosError: true,
            response: { status: 429, data: { message: 'Too Many Attempts.' } },
            message: 'Request failed',
        });

        expect(result.throttled).toBe(true);
        expect(result.message).toMatch(/wait/i);
        expect(result.status).toBe(429);
    });
});
