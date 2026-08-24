import { useEffect } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { dismissAppBootLoader } from '../../lib/appBootLoader';

/**
 * Keeps the HTML boot loader visible until auth has resolved and the shell has painted.
 */
export default function AppBootLoaderGate() {
    const { initializing } = useAuth();

    useEffect(() => {
        if (initializing) {
            return undefined;
        }

        let cancelled = false;

        const frameId = requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                if (cancelled) {
                    return;
                }

                dismissAppBootLoader();
            });
        });

        return () => {
            cancelled = true;
            cancelAnimationFrame(frameId);
        };
    }, [initializing]);

    return null;
}
