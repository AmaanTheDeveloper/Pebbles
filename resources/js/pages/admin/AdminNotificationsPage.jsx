import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import {
    archiveAdminNotification,
    deleteAdminNotification,
    fetchAdminNotifications,
    markAdminNotificationRead,
    markAdminNotificationUnread,
    markAllAdminNotificationsRead,
    unarchiveAdminNotification,
} from '../../api/admin/notifications';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import { AdminPagination } from '../../components/admin/AdminDataTable';
import { formatNotificationTime } from '../../lib/adminNotifications';
import { AdminButton, AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'unread', label: 'Unread' },
    { value: 'read', label: 'Read' },
    { value: 'archived', label: 'Archived' },
];

export default function AdminNotificationsPage() {
    const navigate = useNavigate();
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null, stats: null, categories: [] });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [busyId, setBusyId] = useState(null);
    const [search, setSearch] = useState(searchParams.get('search') ?? '');

    const status = searchParams.get('status') || 'all';
    const category = searchParams.get('category') || '';

    const categoryOptions = useMemo(
        () => [
            { value: '', label: 'All categories' },
            ...(payload.categories ?? []).map((item) => ({ value: item.value, label: item.label })),
        ],
        [payload.categories],
    );

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = {
            page: searchParams.get('page') ?? '1',
            status,
            ...(category ? { category } : {}),
            ...(searchParams.get('search') ? { search: searchParams.get('search') } : {}),
        };

        try {
            const response = await fetchAdminNotifications(params);
            setPayload({
                data: response.data ?? [],
                meta: response.meta ?? null,
                links: response.links ?? null,
                stats: response.stats ?? null,
                categories: response.categories ?? [],
            });
        } catch (err) {
            setError(normalizeApiError(err));
            setPayload({ data: [], meta: null, links: null, stats: null, categories: [] });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
        document.title = 'Notifications — Admin';
    }, [searchParams]);

    const updateParams = (patch) => {
        const next = new URLSearchParams(searchParams);

        Object.entries(patch).forEach(([key, value]) => {
            if (!value || value === 'all') {
                next.delete(key);
            } else {
                next.set(key, value);
            }
        });

        if (!('page' in patch)) {
            next.delete('page');
        }

        setSearchParams(next);
    };

    const applySearch = (event) => {
        event.preventDefault();
        updateParams({ search: search.trim(), status, category });
    };

    const runAction = async (id, action) => {
        setBusyId(id);
        setFlash(null);

        try {
            let response;

            if (action === 'read') {
                response = await markAdminNotificationRead(id);
            } else if (action === 'unread') {
                response = await markAdminNotificationUnread(id);
            } else if (action === 'archive') {
                response = await archiveAdminNotification(id);
            } else if (action === 'unarchive') {
                response = await unarchiveAdminNotification(id);
            } else if (action === 'delete') {
                if (!window.confirm('Delete this notification? The related business record will not be deleted.')) {
                    return;
                }
                response = await deleteAdminNotification(id);
            }

            setFlash({ type: 'success', message: response?.message ?? 'Updated.' });
            await load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setBusyId(null);
        }
    };

    const handleOpen = async (notification) => {
        try {
            if (!notification.is_read && !notification.is_archived) {
                await markAdminNotificationRead(notification.id);
            }
        } catch {
            // Continue navigation.
        }

        if (notification.action_url) {
            navigate(notification.action_url);
            return;
        }

        await load();
    };

    const handleMarkAllRead = async () => {
        setFlash(null);

        try {
            const response = await markAllAdminNotificationsRead();
            setFlash({ type: 'success', message: response?.message ?? 'All notifications marked as read.' });
            await load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    return (
        <div>
            <AdminPageHeader
                title="Notifications"
                description="Centralized alerts for orders, payments, reviews, contact messages, and more."
                actions={
                    <AdminButton type="button" variant="secondary" onClick={handleMarkAllRead}>
                        Mark all as read
                    </AdminButton>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} /> : null}
            {error ? <FlashMessage type="error" message={error.message} /> : null}

            <div className="admin-tabs mb-4">
                {statusTabs.map((tab) => (
                    <button
                        key={tab.value}
                        type="button"
                        onClick={() => updateParams({ status: tab.value, category, search: searchParams.get('search') || '' })}
                        className={`admin-tab${status === tab.value ? ' is-active' : ''}`}
                    >
                        {tab.label}
                        {tab.value === 'unread' && payload.stats?.unread ? ` (${payload.stats.unread})` : ''}
                    </button>
                ))}
            </div>

            <AdminPanel className="mb-4">
                <AdminPanelBody>
                    <form onSubmit={applySearch} className="grid gap-3 md:grid-cols-[1fr_220px_auto]">
                        <AdminFormField
                            label="Search"
                            name="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Title, order number, customer, branch…"
                        />
                        <AdminSelectField
                            label="Category"
                            name="category"
                            value={category}
                            onChange={(event) => updateParams({ status, category: event.target.value, search: search.trim() })}
                            options={categoryOptions}
                        />
                        <div className="flex items-end">
                            <AdminButton type="submit" variant="primary" className="w-full md:w-auto">
                                Search
                            </AdminButton>
                        </div>
                    </form>
                </AdminPanelBody>
            </AdminPanel>

            <AdminPanel>
                <AdminPanelBody className="p-0 sm:p-0">
                    {loading ? (
                        <p className="px-4 py-10 text-center text-sm text-stone-500 sm:px-6">Loading notifications…</p>
                    ) : payload.data.length === 0 ? (
                        <div className="px-4 py-12 text-center sm:px-6">
                            <p className="text-base font-medium text-stone-900">You&apos;re all caught up.</p>
                            <p className="mt-1 text-sm text-stone-500">No notifications match this view.</p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-stone-100">
                            {payload.data.map((notification) => (
                                <li key={notification.id} className={notification.is_read ? 'bg-white' : 'bg-amber-50/30'}>
                                    <div className="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                                        <button
                                            type="button"
                                            className="min-w-0 flex-1 text-left"
                                            onClick={() => handleOpen(notification)}
                                        >
                                            <div className="flex items-start gap-2">
                                                {!notification.is_read ? (
                                                    <span className="mt-2 size-2 shrink-0 rounded-full bg-amber-500" aria-label="Unread" />
                                                ) : (
                                                    <span className="mt-2 size-2 shrink-0 rounded-full bg-stone-300" aria-hidden="true" />
                                                )}
                                                <div className="min-w-0">
                                                    <p className="text-sm font-semibold text-stone-900">{notification.title}</p>
                                                    <p className="mt-1 text-sm text-stone-600">{notification.message}</p>
                                                    <p className="mt-2 text-xs text-stone-400">
                                                        {[
                                                            notification.category_label,
                                                            notification.branch_name,
                                                            notification.priority !== 'normal'
                                                                ? notification.priority
                                                                : null,
                                                            formatNotificationTime(notification.created_at),
                                                        ]
                                                            .filter(Boolean)
                                                            .join(' • ')}
                                                    </p>
                                                </div>
                                            </div>
                                        </button>

                                        <div className="flex flex-wrap gap-2 sm:justify-end">
                                            {notification.is_archived ? (
                                                <ActionButton
                                                    disabled={busyId === notification.id}
                                                    onClick={() => runAction(notification.id, 'unarchive')}
                                                >
                                                    Unarchive
                                                </ActionButton>
                                            ) : (
                                                <ActionButton
                                                    disabled={busyId === notification.id}
                                                    onClick={() => runAction(notification.id, 'archive')}
                                                >
                                                    Archive
                                                </ActionButton>
                                            )}
                                            {notification.is_read ? (
                                                <ActionButton
                                                    disabled={busyId === notification.id}
                                                    onClick={() => runAction(notification.id, 'unread')}
                                                >
                                                    Mark unread
                                                </ActionButton>
                                            ) : (
                                                <ActionButton
                                                    disabled={busyId === notification.id}
                                                    onClick={() => runAction(notification.id, 'read')}
                                                >
                                                    Mark read
                                                </ActionButton>
                                            )}
                                            <ActionButton
                                                disabled={busyId === notification.id}
                                                onClick={() => runAction(notification.id, 'delete')}
                                                danger
                                            >
                                                Delete
                                            </ActionButton>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </AdminPanelBody>
            </AdminPanel>

            {payload.meta ? (
                <div className="mt-4">
                    <AdminPagination
                        meta={payload.meta}
                        links={payload.links}
                        onPageChange={(page) => {
                            const next = new URLSearchParams(searchParams);
                            next.set('page', String(page));
                            setSearchParams(next);
                        }}
                    />
                </div>
            ) : null}
        </div>
    );
}

function ActionButton({ children, onClick, disabled = false, danger = false }) {
    return (
        <button
            type="button"
            disabled={disabled}
            onClick={onClick}
            className={[
                'rounded-md px-2.5 py-1.5 text-xs font-medium ring-1 transition disabled:opacity-50',
                danger
                    ? 'bg-white text-rose-700 ring-rose-200 hover:bg-rose-50'
                    : 'bg-white text-stone-700 ring-stone-200 hover:bg-stone-50',
            ].join(' ')}
        >
            {children}
        </button>
    );
}
