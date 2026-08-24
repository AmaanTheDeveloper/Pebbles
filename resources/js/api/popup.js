import api from './client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchWelcomePopup() {
    const response = await api.get('/api/v1/catalog/popup');

    return unwrap(response);
}
