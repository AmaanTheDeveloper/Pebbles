import OpenStatusBadge from './OpenStatusBadge';

function ContactCard({ href, label, value, icon, external = false }) {
    if (!value) {
        return null;
    }

    const content = (
        <>
            <span className="contact-card-icon" aria-hidden="true">
                {icon}
            </span>
            <span className="space-y-1">
                <span className="block text-xs font-semibold tracking-wide text-brand-green uppercase">{label}</span>
                <span className="block text-sm font-medium text-brand-ink">{value}</span>
            </span>
        </>
    );

    const className = 'contact-card lift-card group';

    if (href) {
        return (
            <a
                href={href}
                className={className}
                {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
            >
                {content}
            </a>
        );
    }

    return <div className={className}>{content}</div>;
}

export default function ContactInfoCards({ contact }) {
    if (!contact) {
        return null;
    }

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <ContactCard href={contact.phone ? `tel:${contact.phone}` : null} label="Phone" value={contact.phone} icon="📞" />
            <ContactCard
                href={contact.whatsapp_url}
                label="WhatsApp"
                value={contact.whatsapp}
                icon="💬"
                external
            />
            <ContactCard href={contact.email ? `mailto:${contact.email}` : null} label="Email" value={contact.email} icon="✉️" />
            <ContactCard
                href={contact.directions_url}
                label="Address"
                value={contact.address}
                icon="📍"
                external
            />
        </div>
    );
}

export function ContactHoursPanel({ contact }) {
    if (!contact?.opening_hours_schedule?.length) {
        return null;
    }

    return (
        <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-green/10 sm:p-6">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h3 className="font-display text-xl font-semibold text-brand-ink">Opening Hours</h3>
                <OpenStatusBadge isOpen={contact.is_open_now} />
            </div>
            <ul className="space-y-2">
                {contact.opening_hours_schedule.map((day) => (
                    <li
                        key={day.day}
                        className="flex items-center justify-between gap-4 border-b border-brand-green/5 py-2 text-sm last:border-b-0"
                    >
                        <span className="font-medium text-brand-ink">{day.label}</span>
                        <span className={day.closed ? 'text-brand-muted' : 'text-brand-muted'}>{day.formatted}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

export function ContactSocialLinks({ social }) {
    const entries = Object.entries(social ?? {}).filter(([, url]) => Boolean(url));

    if (!entries.length) {
        return null;
    }

    const labels = {
        instagram: 'Instagram',
        facebook: 'Facebook',
        tiktok: 'TikTok',
        youtube: 'YouTube',
    };

    return (
        <div className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-brand-green/10 sm:p-6">
            <h3 className="font-display text-xl font-semibold text-brand-ink">Follow Pebbles</h3>
            <div className="mt-4 flex flex-wrap gap-3">
                {entries.map(([platform, url]) => (
                    <a
                        key={platform}
                        href={url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="contact-social-link"
                        aria-label={labels[platform] ?? platform}
                        title={labels[platform] ?? platform}
                    >
                        {labels[platform] ?? platform}
                    </a>
                ))}
            </div>
        </div>
    );
}
