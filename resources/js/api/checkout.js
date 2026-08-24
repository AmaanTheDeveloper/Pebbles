import api from './client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function createOrder(payload) {
    const response = await api.post('/api/v1/checkout', payload);

    return unwrap(response);
}
