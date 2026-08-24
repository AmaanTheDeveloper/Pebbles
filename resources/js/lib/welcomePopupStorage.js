const STORAGE_KEY = 'pebbles_welcome_popup_dismissed';

export function shouldShowWelcomePopup(frequency = 'session') {
    if (frequency === 'visit') {
        return true;
    }

    if (typeof window === 'undefined') {
        return false;
    }

    if (frequency === 'day') {
        const stored = window.localStorage.getItem(STORAGE_KEY);
        if (! stored) {
            return true;
        }

        try {
            const parsed = JSON.parse(stored);
            return parsed?.date !== new Date().toISOString().slice(0, 10);
        } catch {
            return true;
        }
    }

    return window.sessionStorage.getItem(STORAGE_KEY) !== '1';
}

export function markWelcomePopupDismissed(frequency = 'session') {
    if (frequency === 'visit' || typeof window === 'undefined') {
        return;
    }

    if (frequency === 'day') {
        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({ date: new Date().toISOString().slice(0, 10) }),
        );
        return;
    }

    window.sessionStorage.setItem(STORAGE_KEY, '1');
}
