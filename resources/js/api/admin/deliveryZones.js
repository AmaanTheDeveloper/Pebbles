import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminDeliveryZones(params = {}) {
    const response = await api.get('/api/v1/admin/delivery-zones', { params });

    return response.data;
}

export async function fetchAdminDeliveryZone(id) {
    const response = await api.get(`/api/v1/admin/delivery-zones/${id}`);

    return unwrap(response);
}

export async function createAdminDeliveryZone(payload) {
    const response = await api.post('/api/v1/admin/delivery-zones', payload);

    return unwrap(response);
}

export async function updateAdminDeliveryZone(id, payload) {
    const response = await api.patch(`/api/v1/admin/delivery-zones/${id}`, payload);

    return unwrap(response);
}

export async function deleteAdminDeliveryZone(id) {
    const response = await api.delete(`/api/v1/admin/delivery-zones/${id}`);

    return unwrap(response);
}
