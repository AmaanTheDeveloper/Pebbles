/**
 * Server-rendered boot payload from app.blade.php.
 *
 * @returns {{ appName: string, logoUrl: string | null, faviconUrl: string | null } | null}
 */
export function readBootData() {
    if (typeof window === 'undefined') {
        return null;
    }

    const payload = window.__PEBBLES_BOOT__;

    if (!payload || typeof payload !== 'object') {
        return null;
    }

    return {
        appName: typeof payload.appName === 'string' ? payload.appName : 'Pebbles',
        logoUrl: typeof payload.logoUrl === 'string' && payload.logoUrl !== '' ? payload.logoUrl : null,
        faviconUrl:
            typeof payload.faviconUrl === 'string' && payload.faviconUrl !== '' ? payload.faviconUrl : null,
    };
}
