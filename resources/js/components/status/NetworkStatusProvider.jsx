import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import OfflineOverlay from './OfflineOverlay';
import { readBootData } from '../../lib/bootData';

const NetworkStatusContext = createContext({
    isOnline: true,
    setForcedOffline: () => {},
});

export function useNetworkStatus() {
    return useContext(NetworkStatusContext);
}

export default function NetworkStatusProvider({ children }) {
    const [isOnline, setIsOnline] = useState(() =>
        typeof navigator === 'undefined' ? true : navigator.onLine,
    );
    const bootLogoUrl = readBootData()?.logoUrl ?? null;
    const logo = bootLogoUrl ? { url: bootLogoUrl } : null;

    useEffect(() => {
        let cancelled = false;

        const confirmOnline = async () => {
            try {
                const controller = new AbortController();
                const timer = window.setTimeout(() => controller.abort(), 4000);
                const response = await fetch(`/up?_=${Date.now()}`, {
                    method: 'GET',
                    cache: 'no-store',
                    credentials: 'omit',
                    signal: controller.signal,
                });
                window.clearTimeout(timer);
                if (!cancelled && response.ok) {
                    setIsOnline(true);
                }
            } catch {
                // Stay offline until a real probe succeeds.
            }
        };

        const handleOnline = () => {
            void confirmOnline();
        };
        const handleOffline = () => setIsOnline(false);

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            cancelled = true;
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    const setForcedOffline = useCallback((value) => {
        setIsOnline(!value);
    }, []);

    const value = useMemo(
        () => ({
            isOnline,
            setForcedOffline,
        }),
        [isOnline, setForcedOffline],
    );

    return (
        <NetworkStatusContext.Provider value={value}>
            {children}
            {!isOnline ? (
                <OfflineOverlay
                    logo={logo}
                    onRecovered={() => setIsOnline(true)}
                />
            ) : null}
        </NetworkStatusContext.Provider>
    );
}
