import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchDashboard() {
    const response = await api.get('/api/v1/admin/dashboard');

    return unwrap(response);
}
