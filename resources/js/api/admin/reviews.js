import api from '../client';

export async function fetchAdminReviews(params = {}) {
    const response = await api.get('/api/v1/admin/reviews', { params });

    return response.data;
}

export async function fetchAdminReview(id) {
    const response = await api.get(`/api/v1/admin/reviews/${id}`);

    return response.data?.data ?? response.data;
}

export async function updateAdminReview(id, payload) {
    const response = await api.patch(`/api/v1/admin/reviews/${id}`, payload);

    return response.data;
}

export async function deleteAdminReview(id) {
    const response = await api.delete(`/api/v1/admin/reviews/${id}`);

    return response.data;
}
