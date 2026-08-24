import api from '../client';

export async function fetchAdminContactMessages(params = {}) {
    const response = await api.get('/api/v1/admin/contact-messages', { params });

    return response.data;
}

export async function fetchAdminContactMessage(id) {
    const response = await api.get(`/api/v1/admin/contact-messages/${id}`);

    return response.data?.data ?? response.data;
}

export async function updateAdminContactMessage(id, payload) {
    const response = await api.patch(`/api/v1/admin/contact-messages/${id}`, payload);

    return response.data;
}

export async function deleteAdminContactMessage(id) {
    const response = await api.delete(`/api/v1/admin/contact-messages/${id}`);

    return response.data;
}
