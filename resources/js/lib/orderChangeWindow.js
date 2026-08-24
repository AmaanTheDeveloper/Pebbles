export function parseServerTime(value) {
    if (!value) {
        return null;
    }

    const parsed = Date.parse(value);

    return Number.isNaN(parsed) ? null : parsed;
}

export function computeRemainingMs(deadline, serverNow) {
    const deadlineMs = parseServerTime(deadline);
    const serverNowMs = parseServerTime(serverNow);

    if (deadlineMs === null || serverNowMs === null) {
        return 0;
    }

    const offset = serverNowMs - Date.now();

    return Math.max(0, deadlineMs - (Date.now() + offset));
}

export function formatRemaining(ms) {
    const totalSeconds = Math.max(0, Math.floor(ms / 1000));
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

export function formatRemainingLabel(ms) {
    const totalSeconds = Math.max(0, Math.floor(ms / 1000));

    if (totalSeconds <= 0) {
        return '0:00 remaining';
    }

    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    if (minutes > 0 && seconds > 0) {
        return `${minutes}m ${seconds}s remaining`;
    }

    if (minutes > 0) {
        return `${minutes} more minute${minutes === 1 ? '' : 's'}`;
    }

    return `${seconds}s remaining`;
}
