import api from '../client';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function fetchAdminSettings() {
    const response = await api.get('/api/v1/admin/settings');

    return unwrap(response);
}

export async function updateAdminSettings(payload) {
    const response = await api.patch('/api/v1/admin/settings', payload);

    return unwrap(response);
}

export async function uploadAdminLogo(file) {
    const formData = new FormData();
    formData.append('logo', file);

    const response = await api.post('/api/v1/admin/settings/logo', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });

    return response.data;
}

export async function removeAdminLogo() {
    const response = await api.delete('/api/v1/admin/settings/logo');

    return response.data;
}

export async function updateAdminPopupSettings(payload) {
    const response = await api.patch('/api/v1/admin/settings/popup', payload);

    return response.data;
}

export async function uploadAdminPopupImage(file) {
    const formData = new FormData();
    formData.append('image', file);

    const response = await api.post('/api/v1/admin/settings/popup/image', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });

    return response.data;
}

export async function removeAdminPopupImage() {
    const response = await api.delete('/api/v1/admin/settings/popup/image');

    return response.data;
}

export async function updateAdminPaymentSettings(payload) {
    const response = await api.patch('/api/v1/admin/settings/payment', payload);

    return response.data;
}

export async function uploadAdminJazzcashQrImage(file) {
    const formData = new FormData();
    formData.append('image', file);

    const response = await api.post('/api/v1/admin/settings/payment/jazzcash/qr', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });

    return response.data;
}

export async function removeAdminJazzcashQrImage() {
    const response = await api.delete('/api/v1/admin/settings/payment/jazzcash/qr');

    return response.data;
}
