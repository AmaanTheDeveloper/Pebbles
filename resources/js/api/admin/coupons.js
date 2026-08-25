import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminCoupons(params = {}) {
    const response = await api.get('/api/v1/admin/coupons', { params });

    return response.data;
}

export async function fetchAdminCoupon(id) {
    const response = await api.get(`/api/v1/admin/coupons/${id}`);

    return unwrap(response);
}

export async function createAdminCoupon(payload) {
    const response = await api.post('/api/v1/admin/coupons', payload);

    return unwrap(response);
}

export async function updateAdminCoupon(id, payload) {
    const response = await api.patch(`/api/v1/admin/coupons/${id}`, payload);

    return unwrap(response);
}

export async function deleteAdminCoupon(id) {
    await api.delete(`/api/v1/admin/coupons/${id}`);
}
