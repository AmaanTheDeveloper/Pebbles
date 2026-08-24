import api from './client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchPaymentMethods() {
    const response = await api.get('/api/v1/payment-methods');

    return unwrap(response);
}

export async function fetchActiveOrderStatus() {
    const response = await api.get('/api/v1/orders/active-status');

    return Boolean(unwrap(response)?.has_active_orders);
}

export async function fetchOrders({ recentPage = 1 } = {}) {
    const response = await api.get('/api/v1/orders', {
        params: {
            scope: 'all',
            recent_page: recentPage,
        },
    });

    return response.data;
}

export async function fetchOrder(orderNumber) {
    const response = await api.get(`/api/v1/orders/${orderNumber}`);

    return unwrap(response);
}

export async function fetchOrderStatusHistory(orderNumber) {
    const response = await api.get(`/api/v1/orders/${orderNumber}/status-history`);

    return unwrap(response);
}

export async function selectOrderPayment(orderNumber, payload) {
    const response = await api.post(`/api/v1/orders/${orderNumber}/payment`, payload);

    return unwrap(response);
}

export async function uploadPaymentProof(orderNumber, { file, transaction_reference }) {
    const formData = new FormData();
    formData.append('proof', file);

    if (transaction_reference) {
        formData.append('transaction_reference', transaction_reference);
    }

    const response = await api.post(`/api/v1/orders/${orderNumber}/payment/proof`, formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    });

    return unwrap(response);
}

export function paymentProofUrl(orderNumber, proofId) {
    return `/api/v1/orders/${orderNumber}/payment/proof/${proofId}`;
}

export async function cancelOrder(orderNumber, payload = {}) {
    const response = await api.post(`/api/v1/orders/${orderNumber}/cancel`, payload);

    return unwrap(response);
}

export async function previewModifyOrder(orderNumber, payload) {
    const response = await api.post(`/api/v1/orders/${orderNumber}/modify/preview`, payload);

    return unwrap(response);
}

export async function modifyOrder(orderNumber, payload) {
    const response = await api.post(`/api/v1/orders/${orderNumber}/modify`, payload);

    return response.data;
}

export async function reorderOrder(orderNumber, { mode = 'merge' } = {}) {
    const response = await api.post(`/api/v1/orders/${orderNumber}/reorder`, { mode });

    return response.data;
}
