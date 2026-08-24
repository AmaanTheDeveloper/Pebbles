import { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { useLocation } from 'react-router-dom';
import { fetchActiveOrderStatus } from '../api/orders';
import { useVisibilityPolling } from '../hooks/useVisibilityPolling';
import { useAuth } from './AuthContext';

const ActiveOrdersContext = createContext(null);

const TRACKING_PATH = /^\/orders\/[^/]+\/track$/;

export function ActiveOrdersProvider({ children }) {
    const { isAuthenticated, initializing } = useAuth();
    const location = useLocation();
    const [hasActiveOrders, setHasActiveOrders] = useState(false);
    const [loading, setLoading] = useState(false);
    const [hydrated, setHydrated] = useState(false);
    const requestIdRef = useRef(0);
    const isOnTrackingPage = TRACKING_PATH.test(location.pathname);

    const refreshActiveOrders = useCallback(async () => {
        if (!isAuthenticated) {
            setHasActiveOrders(false);
            setHydrated(true);

            return false;
        }

        const requestId = ++requestIdRef.current;
        setLoading(true);

        try {
            const nextValue = await fetchActiveOrderStatus();

            if (requestId === requestIdRef.current) {
                setHasActiveOrders(nextValue);
                setHydrated(true);
            }

            return nextValue;
        } catch {
            if (requestId === requestIdRef.current) {
                setHydrated(true);
            }

            return null;
        } finally {
            if (requestId === requestIdRef.current) {
                setLoading(false);
            }
        }
    }, [isAuthenticated]);

    const notifyActiveOrderCreated = useCallback(() => {
        setHasActiveOrders(true);
        setHydrated(true);
    }, []);

    const notifyActiveOrderClosed = useCallback(() => {
        setHasActiveOrders(false);
        setHydrated(true);
    }, []);

    useEffect(() => {
        if (initializing) {
            return;
        }

        if (!isAuthenticated) {
            setHasActiveOrders(false);
            setHydrated(true);

            return;
        }

        refreshActiveOrders();
    }, [initializing, isAuthenticated, refreshActiveOrders]);

    useVisibilityPolling(
        () => {
            refreshActiveOrders();
        },
        {
            enabled: isAuthenticated && hasActiveOrders && !isOnTrackingPage,
            visibleMs: 20000,
            hiddenMs: 45000,
        },
    );

    useEffect(() => {
        if (!isAuthenticated || isOnTrackingPage) {
            return undefined;
        }

        const onFocus = () => {
            refreshActiveOrders();
        };

        window.addEventListener('focus', onFocus);

        return () => window.removeEventListener('focus', onFocus);
    }, [isAuthenticated, isOnTrackingPage, refreshActiveOrders]);

    const value = useMemo(
        () => ({
            hasActiveOrders,
            loading,
            hydrated,
            refreshActiveOrders,
            notifyActiveOrderCreated,
            notifyActiveOrderClosed,
        }),
        [hasActiveOrders, loading, hydrated, refreshActiveOrders, notifyActiveOrderCreated, notifyActiveOrderClosed],
    );

    return <ActiveOrdersContext.Provider value={value}>{children}</ActiveOrdersContext.Provider>;
}

export function useActiveOrders() {
    const context = useContext(ActiveOrdersContext);

    if (!context) {
        throw new Error('useActiveOrders must be used within an ActiveOrdersProvider.');
    }

    return context;
}
