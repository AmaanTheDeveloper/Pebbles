const icons = {
    dashboard: (
        <path strokeLinecap="round" strokeLinejoin="round" d="M4 5.5h6v6H4zM14 5.5h6v6h-6zM4 14.5h6v6H4zM14 14.5h6v6h-6z" />
    ),
    analytics: (
        <>
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 18V8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M10 18V5" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M16 18v-7" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M22 18V11" />
        </>
    ),
    orders: (
        <>
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 8h10l-1 11H8L7 8z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 8V7a3 3 0 0 1 6 0v1" />
        </>
    ),
    payments: (
        <>
            <rect x="3" y="6" width="18" height="12" rx="2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 10h18" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M7 15h3" />
        </>
    ),
    notifications: (
        <>
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 9a6 6 0 0 1 12 0c0 7 3 7 3 7H3s3 0 3-7" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M10 19a2 2 0 0 0 4 0" />
        </>
    ),
    users: (
        <>
            <circle cx="9" cy="8" r="3" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 19c1.5-3 3.5-4.5 6-4.5s4.5 1.5 6 4.5" />
            <circle cx="17" cy="9" r="2.5" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19c.8-1.8 2.2-2.5 3.5-2.5" />
        </>
    ),
    products: (
        <>
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 12v9M4 7.5 12 12l8-4.5" />
        </>
    ),
    categories: (
        <>
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 7h16M4 12h16M4 17h10" />
        </>
    ),
    offers: (
        <>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5 5 9l10 10 4-4z" />
            <circle cx="8" cy="8" r="1.5" />
        </>
    ),
    coupons: (
        <>
            <rect x="4" y="7" width="16" height="10" rx="2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 7v10M16 7v10" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M11 12h2" />
        </>
    ),
    gallery: (
        <>
            <rect x="4" y="5" width="16" height="14" rx="2" />
            <circle cx="9" cy="10" r="1.5" />
            <path strokeLinecap="round" strokeLinejoin="round" d="m6 17 4-4 3 3 5-6 3 4" />
        </>
    ),
    menuBook: (
        <>
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 5h9a3 3 0 0 1 3 3v12H9a3 3 0 0 1-3-3z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 5a3 3 0 0 0-3 3v12h3" />
        </>
    ),
    homepage: (
        <>
            <path strokeLinecap="round" strokeLinejoin="round" d="M4 10.5 12 4l8 6.5V19a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z" />
        </>
    ),
    about: (
        <>
            <circle cx="12" cy="12" r="8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 10v5M12 7.5h.01" />
        </>
    ),
    reviews: (
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5 14.2 9l4.8.7-3.5 3.4.8 4.8L12 15.8 7.7 17.9l.8-4.8L5 9.7l4.8-.7L12 4.5z" />
    ),
    contact: (
        <>
            <rect x="4" y="6" width="16" height="12" rx="2" />
            <path strokeLinecap="round" strokeLinejoin="round" d="m4 8 8 5 8-5" />
        </>
    ),
    addOns: (
        <>
            <circle cx="12" cy="12" r="8" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v8M8 12h8" />
        </>
    ),
    branches: (
        <>
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 21s6-4.5 6-10a6 6 0 1 0-12 0c0 5.5 6 10 6 10z" />
            <circle cx="12" cy="11" r="2" />
        </>
    ),
    deliveryZones: (
        <>
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 7h11v8H3z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M14 10h4l3 3v2h-7z" />
            <circle cx="7.5" cy="17" r="1.5" />
            <circle cx="17.5" cy="17" r="1.5" />
        </>
    ),
    settings: (
        <>
            <circle cx="12" cy="12" r="3" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4l1.4-1.4M17 7l1.4-1.4" />
        </>
    ),
};

export default function AdminNavIcon({ name, className = 'size-[18px]' }) {
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.75" aria-hidden="true">
            {icons[name] ?? icons.dashboard}
        </svg>
    );
}
