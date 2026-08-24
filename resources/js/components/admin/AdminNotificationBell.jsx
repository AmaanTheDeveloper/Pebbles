import { useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAdminNotifications } from '../../contexts/AdminNotificationsContext';
import { formatNotificationTime } from '../../lib/adminNotifications';

function NotificationIcon({ type }) {
    const common = 'size-4 shrink-0';

    if (type === 'payment') {
        return (
            <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                <rect x="3" y="6" width="18" height="12" rx="2" />
                <path d="M3 10h18" />
                <path d="M7 15h3" />
            </svg>
        );
    }

    if (type === 'review') {
        return (
            <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                <path d="M12 3l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 15.9 7.2 18l.9-5.4L4.2 8.7l5.4-.8L12 3z" />
            </svg>
        );
    }

    if (type === 'contact') {
        return (
            <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                <path d="M4 6h16v12H4z" />
                <path d="M4 7l8 6 8-6" />
            </svg>
        );
    }

    if (type === 'customer') {
        return (
            <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                <circle cx="12" cy="8" r="3.5" />
                <path d="M5 19c1.5-3.5 4-5 7-5s5.5 1.5 7 5" />
            </svg>
        );
    }

    return (
        <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
            <path d="M6 8h12l-1 11H7L6 8z" />
            <path d="M9 8V7a3 3 0 0 1 6 0v1" />
        </svg>
    );
}

export default function AdminNotificationBell() {
    const navigate = useNavigate();
    const { unreadCount, recent, justArrived, refresh, markRead, markAllRead } = useAdminNotifications();
    const [open, setOpen] = useState(false);
    const rootRef = useRef(null);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const onPointerDown = (event) => {
            if (!rootRef.current?.contains(event.target)) {
                setOpen(false);
            }
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open]);

    const openDropdown = async () => {
        const next = !open;
        setOpen(next);

        if (next) {
            await refresh({ force: true });
        }
    };

    const handleOpenNotification = async (notification) => {
        try {
            if (!notification.is_read) {
                await markRead(notification.id);
            }
        } catch {
            // Still navigate even if mark-read fails.
        }

        setOpen(false);

        if (notification.action_url) {
            navigate(notification.action_url);
        } else {
            navigate('/admin/notifications');
        }
    };

    const handleMarkAllRead = async () => {
        try {
            await markAllRead();
        } catch {
            // Keep dropdown usable.
        }
    };

    return (
        <div className="relative" ref={rootRef}>
            <button
                type="button"
                className={[
                    'admin-notification-bell admin-icon-btn relative',
                    justArrived ? 'is-pulse' : '',
                ]
                    .filter(Boolean)
                    .join(' ')}
                aria-label={unreadCount > 0 ? `Notifications, ${unreadCount} unread` : 'Notifications'}
                aria-expanded={open}
                aria-haspopup="dialog"
                onClick={openDropdown}
            >
                <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true">
                    <path d="M6 9a6 6 0 0 1 12 0c0 7 3 7 3 7H3s3 0 3-7" />
                    <path d="M10 19a2 2 0 0 0 4 0" />
                </svg>
                {unreadCount > 0 ? (
                    <span
                        className={[
                            'admin-notification-badge absolute -top-1.5 -right-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-brand-orange px-1.5 py-0.5 text-[10px] font-bold text-white shadow-sm',
                            justArrived ? 'is-pulse' : '',
                        ]
                            .filter(Boolean)
                            .join(' ')}
                    >
                        {unreadCount > 99 ? '99+' : unreadCount}
                    </span>
                ) : null}
            </button>

            {open ? (
                <div
                    className="admin-dropdown absolute right-0 z-40 mt-2 w-[min(100vw-2rem,22rem)]"
                    role="dialog"
                    aria-label="Notifications"
                >
                    <div className="flex items-center justify-between border-b border-brand-green/10 px-4 py-3">
                        <p className="text-sm font-semibold text-brand-ink">Notifications</p>
                        {unreadCount > 0 ? (
                            <button
                                type="button"
                                className="admin-btn admin-btn-ghost px-2 py-1 text-xs"
                                onClick={handleMarkAllRead}
                            >
                                Mark all read
                            </button>
                        ) : null}
                    </div>

                    <div className="max-h-96 overflow-y-auto">
                        {recent.length === 0 ? (
                            <div className="px-4 py-10 text-center">
                                <p className="text-sm font-semibold text-brand-ink">You&apos;re all caught up.</p>
                                <p className="mt-1 text-xs text-brand-muted">No new notifications.</p>
                            </div>
                        ) : (
                            <ul className="divide-y divide-brand-green/5">
                                {recent.map((notification) => (
                                    <li key={notification.id}>
                                        <button
                                            type="button"
                                            className={[
                                                'flex w-full gap-3 px-4 py-3 text-left transition-colors duration-200 hover:bg-brand-green/5',
                                                notification.is_read ? 'bg-white' : 'bg-brand-orange/5',
                                            ].join(' ')}
                                            onClick={() => handleOpenNotification(notification)}
                                        >
                                            <span
                                                className={[
                                                    'mt-0.5 inline-flex size-8 items-center justify-center rounded-full transition-transform duration-200',
                                                    notification.is_read
                                                        ? 'bg-stone-100 text-stone-500'
                                                        : 'bg-brand-orange/15 text-brand-orange',
                                                ].join(' ')}
                                            >
                                                <NotificationIcon type={notification.icon} />
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <span className="flex items-start gap-2">
                                                    {!notification.is_read ? (
                                                        <span className="mt-1.5 size-1.5 shrink-0 rounded-full bg-brand-orange" aria-hidden="true" />
                                                    ) : null}
                                                    <span className="min-w-0">
                                                        <span className="block text-sm font-semibold text-brand-ink">
                                                            {notification.title}
                                                        </span>
                                                        <span className="mt-0.5 block line-clamp-2 text-xs text-brand-muted">
                                                            {notification.message}
                                                        </span>
                                                        <span className="mt-1 block text-[11px] text-stone-400">
                                                            {[notification.branch_name, formatNotificationTime(notification.created_at)]
                                                                .filter(Boolean)
                                                                .join(' • ')}
                                                        </span>
                                                    </span>
                                                </span>
                                            </span>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div className="border-t border-brand-green/10 px-4 py-3">
                        <Link
                            to="/admin/notifications"
                            className="admin-link block text-center text-sm"
                            onClick={() => setOpen(false)}
                        >
                            View All Notifications
                        </Link>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
