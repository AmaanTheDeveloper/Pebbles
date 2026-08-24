import api from '../client';

function unwrap(response) {
    return response.data;
}

export async function fetchAdminNotifications(params = {}) {
    const response = await api.get('/api/v1/admin/notifications', { params });

    return unwrap(response);
}

export async function fetchAdminNotificationUnreadCount() {
    const response = await api.get('/api/v1/admin/notifications/unread-count');

    return Number(unwrap(response)?.data?.unread_count ?? 0);
}

export async function fetchAdminRecentNotifications(limit = 8) {
    const response = await api.get('/api/v1/admin/notifications/recent', {
        params: { limit },
    });

    return unwrap(response);
}

export async function markAdminNotificationRead(id) {
    const response = await api.post(`/api/v1/admin/notifications/${id}/read`);

    return unwrap(response);
}

export async function markAdminNotificationUnread(id) {
    const response = await api.post(`/api/v1/admin/notifications/${id}/unread`);

    return unwrap(response);
}

export async function markAllAdminNotificationsRead() {
    const response = await api.post('/api/v1/admin/notifications/mark-all-read');

    return unwrap(response);
}

export async function archiveAdminNotification(id) {
    const response = await api.post(`/api/v1/admin/notifications/${id}/archive`);

    return unwrap(response);
}

export async function unarchiveAdminNotification(id) {
    const response = await api.post(`/api/v1/admin/notifications/${id}/unarchive`);

    return unwrap(response);
}

export async function deleteAdminNotification(id) {
    const response = await api.delete(`/api/v1/admin/notifications/${id}`);

    return unwrap(response);
}
