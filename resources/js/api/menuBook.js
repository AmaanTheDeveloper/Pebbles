import api from './client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchMenuBookPages() {
    const response = await api.get('/api/v1/catalog/menu-book');

    return unwrap(response) ?? [];
}
