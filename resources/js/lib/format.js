export function formatMoney(value) {
    return `Rs ${Number(value ?? 0).toLocaleString('en-PK', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

export function formatDate(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('en-PK', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export function humanizeStatus(value) {
    if (!value) {
        return '—';
    }

    return String(value).replaceAll('_', ' ');
}
