import { useEffect, useRef } from 'react';

/**
 * Poll only while the tab is visible, with slower intervals when hidden.
 * Refreshes immediately when the tab becomes visible again.
 * Cleared timers on unmount; restarts cleanly when interval options change.
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
        let stopped = false;

        const clearTimer = () => {
            if (timerId !== null) {
                window.clearInterval(timerId);
                timerId = null;
            }
        };

        const tick = () => {
            if (stopped) {
                return;
            }

            try {
                callbackRef.current();
            } catch {
                // Caller failures must not break the polling loop.
            }
        };

        const schedule = () => {
            clearTimer();

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
            stopped = true;
            clearTimer();
            document.removeEventListener('visibilitychange', onVisibilityChange);
        };
    }, [enabled, visibleMs, hiddenMs]);
}
