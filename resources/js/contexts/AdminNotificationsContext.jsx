import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import {
    fetchAdminNotificationUnreadCount,
    fetchAdminRecentNotifications,
    markAdminNotificationRead,
    markAllAdminNotificationsRead,
} from '../api/admin/notifications';
import { useVisibilityPolling } from '../hooks/useVisibilityPolling';
import { useAuth } from './AuthContext';

const AdminNotificationsContext = createContext(null);

const MIN_REFRESH_GAP_MS = 4000;
const MAX_FAILURE_BACKOFF_MS = 60000;

export function AdminNotificationsProvider({ children }) {
    const { isSuperadmin, isManager, initializing } = useAuth();
    const [unreadCount, setUnreadCount] = useState(0);
    const [recent, setRecent] = useState([]);
    const [loading, setLoading] = useState(false);
    const [justArrived, setJustArrived] = useState(false);
    const previousUnreadRef = useRef(null);
    const requestIdRef = useRef(0);
    const lastRefreshAtRef = useRef(0);
    const consecutiveFailuresRef = useRef(0);
    const nextAllowedAtRef = useRef(0);
    const canAccessAdmin = isSuperadmin || isManager;

    const refresh = useCallback(async ({ force = false } = {}) => {
        if (!canAccessAdmin) {
            setUnreadCount(0);
            setRecent([]);
            consecutiveFailuresRef.current = 0;
            nextAllowedAtRef.current = 0;

            return;
        }

        const now = Date.now();

        if (!force) {
            if (now < nextAllowedAtRef.current) {
                return;
            }

            if (now - lastRefreshAtRef.current < MIN_REFRESH_GAP_MS) {
                return;
            }
        }

        const requestId = ++requestIdRef.current;

        if (force) {
            setLoading(true);
        }

        try {
            const [count, recentPayload] = await Promise.all([
                fetchAdminNotificationUnreadCount(),
                fetchAdminRecentNotifications(8),
            ]);

            if (requestId !== requestIdRef.current) {
                return;
            }

            lastRefreshAtRef.current = Date.now();
            consecutiveFailuresRef.current = 0;
            nextAllowedAtRef.current = 0;

            const nextCount = Number(count ?? 0);
            const previous = previousUnreadRef.current;

            if (previous !== null && nextCount > previous) {
                setJustArrived(true);
                window.setTimeout(() => setJustArrived(false), 900);
            }

            previousUnreadRef.current = nextCount;
            setUnreadCount(nextCount);
            setRecent(recentPayload?.data ?? []);
        } catch {
            // Keep navbar usable if polling fails; back off before the next silent poll.
            consecutiveFailuresRef.current += 1;
            const delay = Math.min(
                MAX_FAILURE_BACKOFF_MS,
                MIN_REFRESH_GAP_MS * 2 ** Math.min(consecutiveFailuresRef.current, 4),
            );
            nextAllowedAtRef.current = Date.now() + delay;
        } finally {
            if (force && requestId === requestIdRef.current) {
                setLoading(false);
            }
        }
    }, [canAccessAdmin]);

    useEffect(() => {
        if (initializing || !canAccessAdmin) {
            return undefined;
        }

        refresh({ force: true });

        return undefined;
    }, [initializing, canAccessAdmin, refresh]);

    useVisibilityPolling(
        () => {
            refresh();
        },
        {
            enabled: canAccessAdmin && !initializing,
            visibleMs: 15000,
            hiddenMs: 60000,
        },
    );

    useEffect(() => {
        if (!canAccessAdmin) {
            return undefined;
        }

        const onFocus = () => refresh({ force: true });

        window.addEventListener('focus', onFocus);

        return () => window.removeEventListener('focus', onFocus);
    }, [canAccessAdmin, refresh]);

    const markRead = useCallback(
        async (id) => {
            const response = await markAdminNotificationRead(id);
            const nextCount = Number(response?.meta?.unread_count ?? Math.max(0, unreadCount - 1));
            setUnreadCount(nextCount);
            previousUnreadRef.current = nextCount;
            setRecent((items) =>
                items.map((item) => (item.id === id ? { ...item, ...response.data, is_read: true } : item)),
            );

            return response;
        },
        [unreadCount],
    );

    const markAllRead = useCallback(async () => {
        const response = await markAllAdminNotificationsRead();
        setUnreadCount(0);
        previousUnreadRef.current = 0;
        setRecent((items) => items.map((item) => ({ ...item, is_read: true, read_at: item.read_at ?? new Date().toISOString() })));

        return response;
    }, []);

    const value = useMemo(
        () => ({
            unreadCount,
            recent,
            loading,
            justArrived,
            refresh,
            markRead,
            markAllRead,
        }),
        [unreadCount, recent, loading, justArrived, refresh, markRead, markAllRead],
    );

    return <AdminNotificationsContext.Provider value={value}>{children}</AdminNotificationsContext.Provider>;
}

export function useAdminNotifications() {
    const context = useContext(AdminNotificationsContext);

    if (!context) {
        throw new Error('useAdminNotifications must be used within an AdminNotificationsProvider.');
    }

    return context;
}
