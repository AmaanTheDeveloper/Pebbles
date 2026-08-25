import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import EmailVerificationToast, { AUTO_DISMISS_MS, EXIT_ANIMATION_MS } from '../EmailVerificationToast';
import { EmailVerificationReminderProvider, useEmailVerificationReminder } from '../../../contexts/EmailVerificationReminderContext';

vi.mock('../../../contexts/AuthContext', () => ({
    useAuth: vi.fn(),
}));

vi.mock('../../../lib/emailVerification', async () => {
    const actual = await vi.importActual('../../../lib/emailVerification');

    return {
        ...actual,
        resendVerificationEmail: vi.fn(),
    };
});

import { useAuth } from '../../../contexts/AuthContext';
import { resendVerificationEmail } from '../../../lib/emailVerification';

function Trigger({ user }) {
    const { showReminder } = useEmailVerificationReminder();

    return (
        <button type="button" onClick={() => showReminder(user, { reason: 'register' })}>
            Show reminder
        </button>
    );
}

function renderToastHarness(user) {
    useAuth.mockReturnValue({
        user,
        refreshUser: vi.fn(),
    });

    return render(
        <MemoryRouter>
            <EmailVerificationReminderProvider>
                <Trigger user={user} />
                <EmailVerificationToast />
            </EmailVerificationReminderProvider>
        </MemoryRouter>,
    );
}

describe('EmailVerificationToast', () => {
    beforeEach(() => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        resendVerificationEmail.mockReset();
        useAuth.mockReset();
    });

    afterEach(() => {
        cleanup();
        vi.useRealTimers();
    });

    test('shows after registration trigger for unverified user and auto-dismisses', async () => {
        renderToastHarness({
            role: 'user',
            email: 'new@example.com',
            email_verified_at: null,
        });

        fireEvent.click(screen.getByRole('button', { name: 'Show reminder' }));

        const toast = screen.getByRole('status');
        expect(toast).toHaveClass('email-verify-toast');
        expect(screen.getByText('Verify Your Email')).toBeInTheDocument();
        expect(screen.getByText(/new@example.com/)).toBeInTheDocument();

        await vi.advanceTimersByTimeAsync(AUTO_DISMISS_MS + EXIT_ANIMATION_MS + 50);

        await waitFor(() => {
            expect(screen.queryByText('Verify Your Email')).not.toBeInTheDocument();
        });
    });

    test('does not show for verified users', () => {
        renderToastHarness({
            role: 'user',
            email: 'ok@example.com',
            email_verified_at: '2026-08-25T10:00:00Z',
        });

        fireEvent.click(screen.getByRole('button', { name: 'Show reminder' }));

        expect(screen.queryByText('Verify Your Email')).not.toBeInTheDocument();
    });

    test('resend button calls API and shows success', async () => {
        resendVerificationEmail.mockResolvedValue({
            alreadyVerified: false,
            message: 'Verification email sent successfully. Please check your inbox.',
        });

        renderToastHarness({
            role: 'user',
            email: 'resend@example.com',
            email_verified_at: null,
        });

        fireEvent.click(screen.getByRole('button', { name: 'Show reminder' }));

        const resendButton = screen.getByRole('button', { name: 'Resend Verification Email' });
        fireEvent.click(resendButton);

        expect(resendButton).toBeDisabled();

        await waitFor(() => {
            expect(resendVerificationEmail).toHaveBeenCalledTimes(1);
            expect(
                screen.getByText('Verification email sent successfully. Please check your inbox.'),
            ).toBeInTheDocument();
        });
    });

    test('resend shows throttled error message', async () => {
        resendVerificationEmail.mockRejectedValue({
            message: 'Please wait a moment before requesting another verification email.',
            status: 429,
            throttled: true,
            errors: {},
        });

        renderToastHarness({
            role: 'user',
            email: 'throttle@example.com',
            email_verified_at: null,
        });

        fireEvent.click(screen.getByRole('button', { name: 'Show reminder' }));
        fireEvent.click(screen.getByRole('button', { name: 'Resend Verification Email' }));

        await waitFor(() => {
            expect(
                screen.getByText('Please wait a moment before requesting another verification email.'),
            ).toBeInTheDocument();
        });
    });
});
