import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminCategories(params = {}) {
    const response = await api.get('/api/v1/admin/categories', { params });

    return response.data;
}

export async function fetchAdminCategory(id) {
    const response = await api.get(`/api/v1/admin/categories/${id}`);

    return unwrap(response);
}

export async function createAdminCategory(payload) {
    const response = await api.post('/api/v1/admin/categories', payload);

    return unwrap(response);
}

export async function updateAdminCategory(id, payload) {
    const response = await api.patch(`/api/v1/admin/categories/${id}`, payload);

    return unwrap(response);
}

export async function deleteAdminCategory(id) {
    await api.delete(`/api/v1/admin/categories/${id}`);
}
