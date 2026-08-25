import api from './client';
import { cachedCatalogFetch } from '../lib/catalogCache';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchGalleryItems() {
    return cachedCatalogFetch('gallery', async () => {
        const response = await api.get('/api/v1/catalog/gallery');

        return unwrap(response) ?? [];
    }, [], 2 * 60 * 1000);
}
