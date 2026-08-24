export function canAccessAdminRole(role) {
    return role === 'super_admin' || role === 'manager';
}

export function isSuperAdminRole(role) {
    return role === 'super_admin';
}

export function postLoginPath(user, fallback = '/menu') {
    if (canAccessAdminRole(user?.role)) {
        return '/admin/dashboard';
    }

    return fallback;
}
