import api from './client';
import { cachedCatalogFetch } from '../lib/catalogCache';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAboutContent() {
    return cachedCatalogFetch('about', async () => {
        const response = await api.get('/api/v1/catalog/about');

        return unwrap(response) ?? {};
    });
}
