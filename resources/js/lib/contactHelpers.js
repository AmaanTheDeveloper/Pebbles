export const WEEKDAYS = [
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday',
];

export function defaultOpeningHours() {
    return WEEKDAYS.reduce((accumulator, day) => {
        accumulator[day] = {
            closed: false,
            opens: '12:00',
            closes: '23:00',
            note: '',
        };

        return accumulator;
    }, {});
}

export function normalizeOpeningHours(value) {
    const defaults = defaultOpeningHours();

    if (!value || typeof value !== 'object') {
        return defaults;
    }

    return WEEKDAYS.reduce((accumulator, day) => {
        const entry = value[day] ?? {};

        accumulator[day] = {
            closed: Boolean(entry.closed),
            opens: entry.opens ? String(entry.opens).slice(0, 5) : defaults[day].opens,
            closes: entry.closes ? String(entry.closes).slice(0, 5) : defaults[day].closes,
            note: entry.note ? String(entry.note) : '',
        };

        return accumulator;
    }, {});
}

export function formatPhoneHref(phone) {
    if (!phone) {
        return null;
    }

    return `tel:${String(phone).replace(/\s+/g, '')}`;
}

export function formatWhatsAppUrl(number) {
    if (!number) {
        return null;
    }

    const digits = String(number).replace(/\D+/g, '');

    return digits ? `https://wa.me/${digits}` : null;
}

export function formatMailto(email) {
    return email ? `mailto:${email}` : null;
}

export const SOCIAL_LABELS = {
    instagram: 'Instagram',
    facebook: 'Facebook',
    tiktok: 'TikTok',
    youtube: 'YouTube',
};
