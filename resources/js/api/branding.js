import api from './client';
import { cachedCatalogFetch } from '../lib/catalogCache';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchBranding() {
    return cachedCatalogFetch('branding', async () => {
        const response = await api.get('/api/v1/catalog/branding');

        return unwrap(response) ?? { logo: null };
    });
}
