import api from './client';

export async function updateProfile(payload) {
    const response = await api.patch('/api/v1/profile', payload);

    return response.data;
}

export async function uploadProfilePhoto(file) {
    const formData = new FormData();
    formData.append('photo', file);

    const response = await api.post('/api/v1/profile/photo', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });

    return response.data;
}

export async function removeProfilePhoto() {
    const response = await api.delete('/api/v1/profile/photo');

    return response.data;
}

export async function changePassword(payload) {
    const response = await api.patch('/api/v1/profile/password', payload);

    return response.data;
}

export async function deleteAccount(payload) {
    const response = await api.delete('/api/v1/profile/account', { data: payload });

    return response.data;
}
