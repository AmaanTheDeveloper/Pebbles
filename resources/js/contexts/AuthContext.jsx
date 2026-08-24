import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import api from '../api/client';
import { apiBaseUrl } from '../api/config';
import { normalizeApiError } from '../lib/errors';

const AuthContext = createContext(null);

async function ensureCsrfCookie() {
    await api.get('/sanctum/csrf-cookie', {
        baseURL: apiBaseUrl || undefined,
    });
}

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(false);
    const [initializing, setInitializing] = useState(true);
    const [error, setError] = useState(null);

    const clearError = useCallback(() => setError(null), []);

    const refreshUser = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const { data } = await api.get('/api/user');
            setUser(data.data ?? data);
            return data.data ?? data;
        } catch (err) {
            setUser(null);
            const normalized = normalizeApiError(err);

            if (normalized.status !== 401) {
                setError(normalized);
            }

            return null;
        } finally {
            setLoading(false);
            setInitializing(false);
        }
    }, []);

    useEffect(() => {
        refreshUser();
    }, [refreshUser]);

    const login = useCallback(async (credentials) => {
        setLoading(true);
        setError(null);

        try {
            await ensureCsrfCookie();
            const { data } = await api.post('/api/login', credentials);
            const nextUser = data.data ?? data;
            setUser(nextUser);
            return nextUser;
        } catch (err) {
            const normalized = normalizeApiError(err);
            setError(normalized);
            throw normalized;
        } finally {
            setLoading(false);
        }
    }, []);

    const register = useCallback(async (payload) => {
        setLoading(true);
        setError(null);

        try {
            await ensureCsrfCookie();
            const { data } = await api.post('/api/register', payload);
            const nextUser = data.data ?? data;
            setUser(nextUser);
            return nextUser;
        } catch (err) {
            const normalized = normalizeApiError(err);
            setError(normalized);
            throw normalized;
        } finally {
            setLoading(false);
        }
    }, []);

    const logout = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            await api.post('/api/logout');
            setUser(null);
        } catch (err) {
            const normalized = normalizeApiError(err);
            setError(normalized);
            throw normalized;
        } finally {
            setLoading(false);
        }
    }, []);

    const value = useMemo(
        () => ({
            user,
            loading,
            initializing,
            error,
            clearError,
            refreshUser,
            login,
            register,
            logout,
            isAuthenticated: Boolean(user),
            isUser: user?.role === 'user',
            isManager: user?.role === 'manager',
            isSuperAdmin: user?.role === 'super_admin',
            isSuperadmin: user?.role === 'super_admin',
            canAccessAdmin: user?.role === 'super_admin' || user?.role === 'manager',
            branchId: user?.branch_id ?? user?.branch?.id ?? null,
            branchName: user?.branch?.name ?? null,
        }),
        [user, loading, initializing, error, clearError, refreshUser, login, register, logout],
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider.');
    }

    return context;
}
