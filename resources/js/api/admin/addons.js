import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminAddOnGroups(params = {}) {
    const response = await api.get('/api/v1/admin/add-on-groups', { params });

    return response.data;
}

export async function fetchAdminAddOnGroup(id) {
    const response = await api.get(`/api/v1/admin/add-on-groups/${id}`);

    return unwrap(response);
}

export async function createAdminAddOnGroup(payload) {
    const response = await api.post('/api/v1/admin/add-on-groups', payload);

    return unwrap(response);
}

export async function updateAdminAddOnGroup(id, payload) {
    const response = await api.patch(`/api/v1/admin/add-on-groups/${id}`, payload);

    return unwrap(response);
}

export async function deleteAdminAddOnGroup(id) {
    const response = await api.delete(`/api/v1/admin/add-on-groups/${id}`);

    return unwrap(response);
}

export async function createAddOnOption(groupId, payload) {
    const response = await api.post(`/api/v1/admin/add-on-groups/${groupId}/options`, payload);

    return unwrap(response);
}

export async function updateAddOnOption(groupId, optionId, payload) {
    const response = await api.patch(`/api/v1/admin/add-on-groups/${groupId}/options/${optionId}`, payload);

    return unwrap(response);
}
