import api from './client';
import { apiBaseUrl } from './config';

function unwrap(response) {
    return response.data?.data ?? response.data;
}

export async function ensureCsrfCookie() {
    await api.get('/sanctum/csrf-cookie', {
        baseURL: apiBaseUrl || undefined,
    });
}

export async function fetchContactInfo() {
    const response = await api.get('/api/v1/catalog/contact');

    return unwrap(response);
}

export async function submitContactMessage(payload) {
    await ensureCsrfCookie();
    const response = await api.post('/api/v1/catalog/contact-messages', payload);

    return response.data;
}
