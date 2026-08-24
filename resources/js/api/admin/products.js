import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminProducts(params = {}) {
    const response = await api.get('/api/v1/admin/products', { params });

    return response.data;
}

export async function fetchAdminProduct(id) {
    const response = await api.get(`/api/v1/admin/products/${id}`);

    return unwrap(response);
}

export async function createAdminProduct(payload) {
    const response = await api.post('/api/v1/admin/products', payload);

    return unwrap(response);
}

export async function updateAdminProduct(id, payload) {
    const response = await api.patch(`/api/v1/admin/products/${id}`, payload);

    return unwrap(response);
}

export async function deleteAdminProduct(id) {
    const response = await api.delete(`/api/v1/admin/products/${id}`);

    return unwrap(response);
}

export async function createProductVariant(productId, payload) {
    const response = await api.post(`/api/v1/admin/products/${productId}/variants`, payload);

    return unwrap(response);
}

export async function updateProductVariant(productId, variantId, payload) {
    const response = await api.patch(`/api/v1/admin/products/${productId}/variants/${variantId}`, payload);

    return unwrap(response);
}

export async function uploadProductImage(productId, formData) {
    const response = await api.post(`/api/v1/admin/products/${productId}/images`, formData);

    return unwrap(response);
}

export async function setPrimaryProductImage(productId, imageId) {
    const response = await api.post(`/api/v1/admin/products/${productId}/images/${imageId}/primary`);

    return unwrap(response);
}

export async function deleteProductImage(productId, imageId) {
    await api.delete(`/api/v1/admin/products/${productId}/images/${imageId}`);
}
