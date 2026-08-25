import { useCallback, useEffect, useState } from 'react';
import StatusExperience from './StatusExperience';

async function probeConnectivity() {
    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
        return false;
    }

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

        return response.ok;
    } catch {
        return false;
    }
}

/**
 * Full-screen offline experience. Shown when the browser reports offline
 * or a connectivity probe fails after the user taps Try Again.
 */
export default function OfflineOverlay({ logo = null, onRecovered }) {
    const [checking, setChecking] = useState(false);
    const [stillOffline, setStillOffline] = useState(false);

    const handleRetry = useCallback(async () => {
        setChecking(true);
        setStillOffline(false);

        const online = await probeConnectivity();

        setChecking(false);

        if (online) {
            onRecovered?.();
            return;
        }

        setStillOffline(true);
    }, [onRecovered]);

    useEffect(() => {
        const previousTitle = document.title;
        document.title = "You're offline · Pebbles";

        return () => {
            document.title = previousTitle;
        };
    }, []);

    return (
        <div className="status-overlay" role="alertdialog" aria-modal="true" aria-labelledby="offline-title">
            <StatusExperience
                logo={logo}
                brandLink={false}
                titleId="offline-title"
                eyebrow="Connection lost"
                title="Oops! You're offline"
                description="It looks like your internet connection was interrupted. Please reconnect and try again."
                illustration="offline"
            >
                <button
                    type="button"
                    onClick={handleRetry}
                    disabled={checking}
                    className="btn-orange btn-shine status-page-btn disabled:opacity-60"
                >
                    {checking ? 'Checking…' : 'Try Again'}
                </button>
                {stillOffline ? (
                    <p className="status-page-hint">Still offline. Check your connection and try again.</p>
                ) : null}
            </StatusExperience>
        </div>
    );
}
