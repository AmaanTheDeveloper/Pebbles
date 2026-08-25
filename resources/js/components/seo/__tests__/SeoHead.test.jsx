import { cleanup, render, waitFor } from '@testing-library/react';
import { afterEach, describe, expect, test } from 'vitest';
import SeoHead from '../SeoHead';

describe('SeoHead', () => {
    afterEach(() => {
        cleanup();
        document.title = '';
        document.querySelectorAll('[data-pebbles-seo]').forEach((el) => el.remove());
    });

    test('sets document title with app name suffix', async () => {
        render(
            <SeoHead
                title="Menu"
                description="Browse the menu"
                path="/menu"
            />,
        );

        await waitFor(() => {
            expect(document.title).toMatch(/Menu/);
            expect(document.title).toMatch(/Pebbles/);
        });

        const description = document.querySelector('meta[name="description"]');
        expect(description?.getAttribute('content')).toBe('Browse the menu');

        const canonical = document.querySelector('link[rel="canonical"]');
        expect(canonical?.getAttribute('href')).toContain('/menu');
    });

    test('applies noindex robots when requested', async () => {
        render(<SeoHead title="Private" noindex path="/account" />);

        await waitFor(() => {
            expect(document.querySelector('meta[name="robots"]')?.getAttribute('content')).toBe(
                'noindex, nofollow',
            );
        });
    });
});
