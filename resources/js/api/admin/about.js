import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminAbout() {
    const response = await api.get('/api/v1/admin/about');

    return unwrap(response);
}

export async function updateAdminAbout(payload) {
    const response = await api.patch('/api/v1/admin/about', payload);

    return response.data;
}
