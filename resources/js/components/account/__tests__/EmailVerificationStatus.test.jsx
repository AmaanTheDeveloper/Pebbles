import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, test, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import EmailVerificationStatus from '../EmailVerificationStatus';

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

describe('EmailVerificationStatus', () => {
    afterEach(() => {
        cleanup();
        vi.clearAllMocks();
    });

    test('shows verified badge for verified users without resend', () => {
        useAuth.mockReturnValue({
            user: {
                email: 'verified@example.com',
                email_verified_at: '2026-08-25T10:00:00Z',
                role: 'user',
            },
            refreshUser: vi.fn(),
        });

        render(
            <MemoryRouter>
                <EmailVerificationStatus />
            </MemoryRouter>,
        );

        expect(screen.getByText('Email Verified')).toBeInTheDocument();
        expect(screen.getByText('verified@example.com')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /Resend Verification Email/i })).not.toBeInTheDocument();
    });

    test('shows warning and resend for unverified users', async () => {
        const refreshUser = vi.fn();
        useAuth.mockReturnValue({
            user: {
                email: 'pending@example.com',
                email_verified_at: null,
                role: 'user',
            },
            refreshUser,
        });

        resendVerificationEmail.mockResolvedValue({
            alreadyVerified: false,
            message: 'Verification email sent. Please check your inbox.',
        });

        render(
            <MemoryRouter>
                <EmailVerificationStatus />
            </MemoryRouter>,
        );

        expect(screen.getByText('Your email address is not verified.')).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: /Resend Verification Email/i }));

        await waitFor(() => {
            expect(resendVerificationEmail).toHaveBeenCalledTimes(1);
            expect(screen.getByText(/Verification email sent/i)).toBeInTheDocument();
        });
    });
});
