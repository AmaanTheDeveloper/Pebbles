import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminMenuBookPages(params = {}) {
    const response = await api.get('/api/v1/admin/menu-book', { params });

    return response.data;
}

export async function fetchAdminMenuBookPage(id) {
    const response = await api.get(`/api/v1/admin/menu-book/${id}`);

    return unwrap(response);
}

export async function createAdminMenuBookPage(formData) {
    const response = await api.post('/api/v1/admin/menu-book', formData);

    return response.data;
}

export async function updateAdminMenuBookPage(id, formData) {
    if (formData instanceof FormData) {
        formData.append('_method', 'PATCH');
        const response = await api.post(`/api/v1/admin/menu-book/${id}`, formData);

        return response.data;
    }

    const response = await api.patch(`/api/v1/admin/menu-book/${id}`, formData);

    return response.data;
}

export async function deleteAdminMenuBookPage(id) {
    const response = await api.delete(`/api/v1/admin/menu-book/${id}`);

    return response.data;
}

export async function toggleAdminMenuBookPageStatus(id, isActive) {
    const response = await api.patch(`/api/v1/admin/menu-book/${id}/status`, {
        is_active: isActive,
    });

    return response.data;
}
