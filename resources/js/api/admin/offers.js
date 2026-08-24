import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminOffers(params = {}) {
    const response = await api.get('/api/v1/admin/offers', { params });

    return response.data;
}

export async function fetchAdminOffer(id) {
    const response = await api.get(`/api/v1/admin/offers/${id}`);

    return unwrap(response);
}

export async function createAdminOffer(formData) {
    const response = await api.post('/api/v1/admin/offers', formData);

    return unwrap(response);
}

export async function updateAdminOffer(id, formData) {
    formData.append('_method', 'PATCH');
    const response = await api.post(`/api/v1/admin/offers/${id}`, formData);

    return unwrap(response);
}

export async function deleteAdminOffer(id) {
    await api.delete(`/api/v1/admin/offers/${id}`);
}
