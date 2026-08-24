import api from './client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchGalleryItems() {
    const response = await api.get('/api/v1/catalog/gallery');

    return unwrap(response) ?? [];
}
