/**
 * Central API base URL for the Pebbles SPA.
 *
 * When blank, Axios uses same-origin relative paths (recommended with the
 * Laravel-served SPA shell). Set VITE_API_BASE_URL when the SPA is hosted on
 * a separate origin during local development.
 */
export const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? '';

export const appName = import.meta.env.VITE_APP_NAME ?? 'Pebbles';
