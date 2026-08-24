import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminGalleryItems(params = {}) {
    const response = await api.get('/api/v1/admin/gallery', { params });

    return response.data;
}

export async function fetchAdminGalleryItem(id) {
    const response = await api.get(`/api/v1/admin/gallery/${id}`);

    return unwrap(response);
}

export async function createAdminGalleryItem(formData) {
    const response = await api.post('/api/v1/admin/gallery', formData);

    return response.data;
}

export async function updateAdminGalleryItem(id, formData) {
    if (formData instanceof FormData) {
        formData.append('_method', 'PATCH');
        const response = await api.post(`/api/v1/admin/gallery/${id}`, formData);

        return response.data;
    }

    const response = await api.patch(`/api/v1/admin/gallery/${id}`, formData);

    return response.data;
}

export async function deleteAdminGalleryItem(id) {
    const response = await api.delete(`/api/v1/admin/gallery/${id}`);

    return response.data;
}

export async function toggleAdminGalleryItemStatus(id, isActive) {
    const response = await api.patch(`/api/v1/admin/gallery/${id}/status`, {
        is_active: isActive,
    });

    return response.data;
}
