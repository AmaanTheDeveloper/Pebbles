import { computeRemainingMs } from './orderChangeWindow';

export function formatEstimateRemaining(ms) {
    const totalSeconds = Math.max(0, Math.floor(ms / 1000));

    if (totalSeconds === 0) {
        return {
            text: '0 min',
            expired: true,
            isFinalMinute: false,
        };
    }

    if (totalSeconds < 60) {
        return {
            text: `${totalSeconds} sec remaining`,
            expired: false,
            isFinalMinute: true,
        };
    }

    return {
        text: `${Math.floor(totalSeconds / 60)} min remaining`,
        expired: false,
        isFinalMinute: false,
    };
}

export { computeRemainingMs };
