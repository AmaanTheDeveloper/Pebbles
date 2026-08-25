const STORAGE_KEY = 'pebbles.orderType';

/** @typedef {'delivery' | 'takeaway' | 'dine_in'} OrderTypeChoice */

/**
 * @returns {OrderTypeChoice|null}
 */
export function readOrderType() {
    try {
        const value = localStorage.getItem(STORAGE_KEY);

        if (value === 'delivery' || value === 'takeaway' || value === 'dine_in') {
            return value;
        }

        return null;
    } catch {
        return null;
    }
}

/**
 * @param {OrderTypeChoice|null} type
 */
export function writeOrderType(type) {
    try {
        if (type !== 'delivery' && type !== 'takeaway' && type !== 'dine_in') {
            localStorage.removeItem(STORAGE_KEY);

            return;
        }

        localStorage.setItem(STORAGE_KEY, type);
    } catch {
        // Ignore storage failures (private mode, quota, etc.).
    }
}
