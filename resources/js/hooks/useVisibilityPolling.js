import { useEffect, useRef } from 'react';

/**
 * Poll only while the tab is visible, with slower intervals when hidden.
 * Refreshes immediately when the tab becomes visible again.
 */
export function useVisibilityPolling(callback, { enabled = true, visibleMs = 8000, hiddenMs = 30000 } = {}) {
    const callbackRef = useRef(callback);

    useEffect(() => {
        callbackRef.current = callback;
    }, [callback]);

    useEffect(() => {
        if (!enabled) {
            return undefined;
        }

        let timerId = null;

        const tick = () => {
            callbackRef.current();
        };

        const schedule = () => {
            if (timerId !== null) {
                window.clearInterval(timerId);
            }

            const intervalMs = document.hidden ? hiddenMs : visibleMs;
            timerId = window.setInterval(tick, intervalMs);
        };

        const onVisibilityChange = () => {
            if (!document.hidden) {
                tick();
            }

            schedule();
        };

        schedule();

        document.addEventListener('visibilitychange', onVisibilityChange);

        return () => {
            if (timerId !== null) {
                window.clearInterval(timerId);
            }

            document.removeEventListener('visibilitychange', onVisibilityChange);
        };
    }, [enabled, visibleMs, hiddenMs]);
}
