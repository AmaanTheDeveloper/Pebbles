import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminHomepage() {
    const response = await api.get('/api/v1/admin/homepage');

    return unwrap(response);
}

export async function updateAdminHomepageSection(key, payload) {
    const isFormData = payload instanceof FormData;
    const response = await api.patch(`/api/v1/admin/homepage/sections/${key}`, payload, isFormData ? {
        headers: { 'Content-Type': 'multipart/form-data' },
    } : undefined);

    return response.data;
}

export async function reorderAdminHomepageSections(sections) {
    const response = await api.patch('/api/v1/admin/homepage/sections/reorder', { sections });

    return response.data;
}

export async function createAdminHomepageFeature(payload) {
    const response = await api.post('/api/v1/admin/homepage/features', payload);

    return response.data;
}

export async function updateAdminHomepageFeature(id, payload) {
    const response = await api.patch(`/api/v1/admin/homepage/features/${id}`, payload);

    return response.data;
}

export async function deleteAdminHomepageFeature(id) {
    const response = await api.delete(`/api/v1/admin/homepage/features/${id}`);

    return response.data;
}

export async function createAdminHomepageCtaCard(payload) {
    const response = await api.post('/api/v1/admin/homepage/cta-cards', payload);

    return response.data;
}

export async function updateAdminHomepageCtaCard(id, payload) {
    const response = await api.patch(`/api/v1/admin/homepage/cta-cards/${id}`, payload);

    return response.data;
}

export async function deleteAdminHomepageCtaCard(id) {
    const response = await api.delete(`/api/v1/admin/homepage/cta-cards/${id}`);

    return response.data;
}

export async function createAdminHomepageShowcaseItem(payload) {
    const response = await api.post('/api/v1/admin/homepage/showcase-items', payload);

    return response.data;
}

export async function updateAdminHomepageShowcaseItem(id, payload) {
    const response = await api.patch(`/api/v1/admin/homepage/showcase-items/${id}`, payload);

    return response.data;
}

export async function deleteAdminHomepageShowcaseItem(id) {
    const response = await api.delete(`/api/v1/admin/homepage/showcase-items/${id}`);

    return response.data;
}
