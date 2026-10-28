import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import ProfileForm from '../ProfileForm';

vi.mock('../../../contexts/AuthContext', () => ({
    useAuth: vi.fn(),
}));

vi.mock('../../../api/profile', () => ({
    updateProfile: vi.fn(),
    uploadProfilePhoto: vi.fn(),
    removeProfilePhoto: vi.fn(),
    changePassword: vi.fn(),
    deleteAccount: vi.fn(),
}));

import { useAuth } from '../../../contexts/AuthContext';
import { updateProfile } from '../../../api/profile';

describe('ProfileForm address payload', () => {
    afterEach(() => {
        cleanup();
        vi.clearAllMocks();
    });

    beforeEach(() => {
        useAuth.mockReturnValue({
            user: {
                id: 1,
                name: 'GH TESTER',
                email: 'gh@example.com',
                phone: null,
                role: 'user',
                role_label: 'Customer',
                default_address: null,
                profile_photo: null,
            },
            refreshUser: vi.fn(),
            logout: vi.fn(),
        });
    });

    test('name-only save does not send a city-only address payload', async () => {
        updateProfile.mockResolvedValue({ message: 'Profile updated successfully.' });

        render(
            <MemoryRouter>
                <ProfileForm showAddress showDeleteAccount={false} />
            </MemoryRouter>,
        );

        fireEvent.change(screen.getByLabelText(/Full name/i), {
            target: { value: 'GH TESTER Updated' },
        });
        fireEvent.click(screen.getByRole('button', { name: /Save profile/i }));

        await waitFor(() => {
            expect(updateProfile).toHaveBeenCalledTimes(1);
        });

        expect(updateProfile).toHaveBeenCalledWith({
            name: 'GH TESTER Updated',
            email: 'gh@example.com',
            phone: null,
        });
    });

    test('address with line1 requires phone before calling API', async () => {
        render(
            <MemoryRouter>
                <ProfileForm showAddress showDeleteAccount={false} />
            </MemoryRouter>,
        );

        fireEvent.change(screen.getByLabelText(/^Address$/i), {
            target: { value: 'Street 12' },
        });
        fireEvent.click(screen.getByRole('button', { name: /Save profile/i }));

        await waitFor(() => {
            expect(screen.getAllByText(/phone number is required/i).length).toBeGreaterThan(0);
        });

        expect(updateProfile).not.toHaveBeenCalled();
    });
});
