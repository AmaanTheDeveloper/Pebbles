import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminOrders(params = {}) {
    const response = await api.get('/api/v1/admin/orders', { params });

    return response.data;
}

export async function fetchAdminOrder(orderNumber) {
    const response = await api.get(`/api/v1/admin/orders/${orderNumber}`);

    return unwrap(response);
}

export async function updateAdminOrderStatus(orderNumber, payload) {
    const response = await api.patch(`/api/v1/admin/orders/${orderNumber}/status`, payload);

    return unwrap(response);
}

export async function verifyOrderPayment(orderNumber, payload = {}) {
    const response = await api.post(`/api/v1/admin/orders/${orderNumber}/payment/verify`, payload);

    return unwrap(response);
}

export async function rejectOrderPayment(orderNumber, payload = {}) {
    const response = await api.post(`/api/v1/admin/orders/${orderNumber}/payment/reject`, payload);

    return unwrap(response);
}

export async function fetchPaymentProof(orderNumber, proofId) {
    const response = await api.get(`/api/v1/admin/orders/${orderNumber}/payment/proof/${proofId}`, {
        responseType: 'blob',
    });

    return response.data;
}
