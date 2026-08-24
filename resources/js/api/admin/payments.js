import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminPayments(params = {}) {
    const response = await api.get('/api/v1/admin/payments', { params });

    return response.data;
}

export async function fetchAdminPayment(id) {
    const response = await api.get(`/api/v1/admin/payments/${id}`);

    return unwrap(response);
}
