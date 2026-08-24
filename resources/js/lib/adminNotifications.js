export function formatNotificationTime(value) {
    if (!value) {
        return '';
    }

    const date = value instanceof Date ? value : new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    const now = Date.now();
    const diffMs = now - date.getTime();
    const diffSeconds = Math.round(diffMs / 1000);

    if (diffSeconds < 45) {
        return 'just now';
    }

    const diffMinutes = Math.round(diffSeconds / 60);

    if (diffMinutes < 60) {
        return `${diffMinutes} min ago`;
    }

    const diffHours = Math.round(diffMinutes / 60);

    if (diffHours < 24) {
        return diffHours === 1 ? '1 hour ago' : `${diffHours} hours ago`;
    }

    const diffDays = Math.round(diffHours / 24);

    if (diffDays === 1) {
        return 'Yesterday';
    }

    if (diffDays < 7) {
        return `${diffDays} days ago`;
    }

    return date.toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        year: date.getFullYear() === new Date().getFullYear() ? undefined : 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}
