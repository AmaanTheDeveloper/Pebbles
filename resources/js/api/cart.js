import api from './client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function getCart() {
    const response = await api.get('/api/v1/cart');

    return unwrap(response);
}

export async function addCartItem(payload) {
    const response = await api.post('/api/v1/cart/items', payload);

    return unwrap(response);
}

export async function updateCartItem(itemId, payload) {
    const response = await api.patch(`/api/v1/cart/items/${itemId}`, payload);

    return unwrap(response);
}

export async function removeCartItem(itemId) {
    const response = await api.delete(`/api/v1/cart/items/${itemId}`);

    return unwrap(response);
}

export async function clearCart() {
    await api.delete('/api/v1/cart');
}
