import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAnalyticsOverview(params = {}) {
    const response = await api.get('/api/v1/admin/analytics/overview', { params });

    return unwrap(response);
}

export async function fetchAnalyticsSales(params = {}) {
    const response = await api.get('/api/v1/admin/analytics/sales', { params });

    return unwrap(response);
}

export async function fetchAnalyticsOrders(params = {}) {
    const response = await api.get('/api/v1/admin/analytics/orders', { params });

    return unwrap(response);
}

export async function fetchAnalyticsProducts(params = {}) {
    const response = await api.get('/api/v1/admin/analytics/products', { params });

    return unwrap(response);
}

export async function fetchAnalyticsBranches(params = {}) {
    const response = await api.get('/api/v1/admin/analytics/branches', { params });

    return unwrap(response);
}

export async function fetchAnalyticsCustomers(params = {}) {
    const response = await api.get('/api/v1/admin/analytics/customers', { params });

    return unwrap(response);
}

export async function fetchAnalyticsPaymentMethods(params = {}) {
    const response = await api.get('/api/v1/admin/analytics/payment-methods', { params });

    return unwrap(response);
}

export async function fetchAnalyticsDeliveryZones(params = {}) {
    const response = await api.get('/api/v1/admin/analytics/delivery-zones', { params });

    return unwrap(response);
}

export async function exportAnalytics(params = {}) {
    const response = await api.get('/api/v1/admin/analytics/export', {
        params,
        responseType: 'blob',
    });

    const blob = response.data;
    const disposition = response.headers['content-disposition'];
    let filename = 'pebbles-analytics-export.csv';

    if (disposition) {
        const match = disposition.match(/filename="?([^";]+)"?/);

        if (match?.[1]) {
            filename = match[1];
        }
    }

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}
