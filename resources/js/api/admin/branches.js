import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminBranches(params = {}) {
    const response = await api.get('/api/v1/admin/branches', { params });

    return response.data;
}

export async function fetchAdminBranch(id) {
    const response = await api.get(`/api/v1/admin/branches/${id}`);

    return unwrap(response);
}

export async function createAdminBranch(payload) {
    const response = await api.post('/api/v1/admin/branches', payload, payload instanceof FormData ? undefined : undefined);

    return unwrap(response);
}

export async function updateAdminBranch(id, payload) {
    if (payload instanceof FormData) {
        payload.append('_method', 'PATCH');
        const response = await api.post(`/api/v1/admin/branches/${id}`, payload);

        return unwrap(response);
    }

    const response = await api.patch(`/api/v1/admin/branches/${id}`, payload);

    return unwrap(response);
}

export async function deleteAdminBranch(id) {
    const response = await api.delete(`/api/v1/admin/branches/${id}`);

    return unwrap(response);
}
