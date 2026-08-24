const ICONS = {
    pending: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M7 3h10a2 2 0 0 1 2 2v14l-3-2-3 2-3-2-3 2V5a2 2 0 0 1 2-2Z" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    ),
    confirmed: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M9 12.5 11 14.5 15.5 10" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M7 3h10a2 2 0 0 1 2 2v14l-3-2-3 2-3-2-3 2V5a2 2 0 0 1 2-2Z" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    ),
    preparing: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M6 10h12M8 6h8M9 14h6M10 18h4" strokeLinecap="round" />
        </svg>
    ),
    ready: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M12 3 20 7v6c0 4-3.5 7-8 8-4.5-1-8-4-8-8V7l8-4Z" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    ),
    out_for_delivery: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M3 13h11v5H3zM14 15h3l3 3v-5h-6z" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="7.5" cy="18.5" r="1.5" />
            <circle cx="17.5" cy="18.5" r="1.5" />
        </svg>
    ),
    completed: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="m8 12.5 2.5 2.5L16.5 9" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="12" cy="12" r="9" />
        </svg>
    ),
    cancelled: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="m8.5 8.5 7 7M16.5 8.5 18 7M8.5 15.5 7 17M16.5 15.5 18 17" strokeLinecap="round" />
        </svg>
    ),
};

export default function OrderTrackingStepIcon({ status, state }) {
    const icon = ICONS[status] ?? ICONS.pending;

    return (
        <span
            className={[
                'order-track-step-icon',
                state === 'current' ? 'is-current' : '',
                state === 'complete' ? 'is-complete' : '',
                status === 'out_for_delivery' && state === 'current' ? 'is-delivering' : '',
            ]
                .filter(Boolean)
                .join(' ')}
        >
            {state === 'complete' ? ICONS.completed : icon}
        </span>
    );
}
