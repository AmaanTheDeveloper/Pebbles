import api from '../client';

export async function fetchAdminUsers(params = {}) {
    const response = await api.get('/api/v1/admin/users', { params });

    return response.data;
}

export async function fetchAdminUser(id) {
    const response = await api.get(`/api/v1/admin/users/${id}`);

    return response.data?.data ?? response.data;
}

export async function updateAdminUser(id, payload) {
    const response = await api.patch(`/api/v1/admin/users/${id}`, payload);

    return response.data;
}
