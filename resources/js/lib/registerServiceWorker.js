/**
 * Register a production-only service worker for the offline fallback page.
 * Never registers during Vite HMR / local development.
 *
 * Update strategy:
 * - Call registration.update() on window focus and on a slow interval.
 * - Ask waiting workers to skipWaiting once (no forced page reload loop).
 */
const UPDATE_INTERVAL_MS = 60 * 60 * 1000;
const SKIP_WAITING_FLAG = 'pebbles_sw_skip_waiting_sent';

function askWaitingWorkerToActivate(registration) {
    const waiting = registration.waiting;

    if (!waiting) {
        return;
    }

    // One skipWaiting nudge per tab session — avoids message storms / update loops.
    if (sessionStorage.getItem(SKIP_WAITING_FLAG) === waiting.scriptURL) {
        return;
    }

    sessionStorage.setItem(SKIP_WAITING_FLAG, waiting.scriptURL);
    waiting.postMessage({ type: 'SKIP_WAITING' });
}

function wireRegistration(registration) {
    registration.addEventListener('updatefound', () => {
        const installing = registration.installing;

        if (!installing) {
            return;
        }

        installing.addEventListener('statechange', () => {
            if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                askWaitingWorkerToActivate(registration);
            }
        });
    });

    askWaitingWorkerToActivate(registration);

    const checkForUpdates = () => {
        registration.update().catch(() => {
            // Offline or SW unreachable — ignore.
        });
    };

    window.addEventListener('focus', checkForUpdates);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            checkForUpdates();
        }
    });

    window.setInterval(checkForUpdates, UPDATE_INTERVAL_MS);
}

export function registerServiceWorker() {
    if (typeof window === 'undefined' || !('serviceWorker' in navigator)) {
        return;
    }

    if (!import.meta.env.PROD) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .then(wireRegistration)
            .catch(() => {
                // Offline support is progressive — failure must not break the app.
            });
    });
}
