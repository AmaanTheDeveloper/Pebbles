import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import OfflineOverlay from '../OfflineOverlay';

describe('OfflineOverlay', () => {
    beforeEach(() => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => ({ ok: false })),
        );
    });

    afterEach(() => {
        cleanup();
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    test('renders offline experience and retry keeps still-offline hint when probe fails', async () => {
        render(
            <MemoryRouter>
                <OfflineOverlay />
            </MemoryRouter>,
        );

        expect(screen.getByRole('alertdialog')).toBeInTheDocument();
        expect(screen.getByText("Oops! You're offline")).toBeInTheDocument();

        fireEvent.click(screen.getByRole('button', { name: /try again/i }));

        await waitFor(() => {
            expect(screen.getByText(/still offline/i)).toBeInTheDocument();
        });
        expect(fetch).toHaveBeenCalled();
    });

    test('calls onRecovered when connectivity probe succeeds', async () => {
        fetch.mockResolvedValueOnce({ ok: true });
        const onRecovered = vi.fn();

        render(
            <MemoryRouter>
                <OfflineOverlay onRecovered={onRecovered} />
            </MemoryRouter>,
        );

        fireEvent.click(screen.getByRole('button', { name: /try again/i }));

        await waitFor(() => {
            expect(onRecovered).toHaveBeenCalledTimes(1);
        });
    });
});
