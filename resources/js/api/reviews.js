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

export async function fetchReviewSummary() {
    const response = await api.get('/api/v1/catalog/reviews/summary');

    return unwrap(response);
}

export async function fetchReviews(params = {}) {
    const response = await api.get('/api/v1/catalog/reviews', { params });

    return response.data;
}

export async function submitReview(payload) {
    await ensureCsrfCookie();
    const response = await api.post('/api/v1/catalog/reviews', payload);

    return response.data;
}
