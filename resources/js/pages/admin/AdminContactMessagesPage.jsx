import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
    deleteAdminContactMessage,
    fetchAdminContactMessages,
    updateAdminContactMessage,
} from '../../api/admin/contactMessages';
import AdminDataTable, { AdminPagination } from '../../components/admin/AdminDataTable';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const statusOptions = [
    { value: '', label: 'All statuses' },
    { value: 'new', label: 'New' },
    { value: 'read', label: 'Read' },
    { value: 'replied', label: 'Replied' },
    { value: 'archived', label: 'Archived' },
];

const statusStyles = {
    new: 'bg-sky-100 text-sky-900',
    read: 'bg-amber-100 text-amber-900',
    replied: 'bg-emerald-100 text-emerald-900',
    archived: 'bg-stone-200 text-stone-700',
};

export default function AdminContactMessagesPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null, stats: null });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [search, setSearch] = useState(searchParams.get('search') ?? '');
    const [status, setStatus] = useState(searchParams.get('status') ?? '');
    const [viewing, setViewing] = useState(null);

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = {
            page: searchParams.get('page') ?? '1',
            ...(searchParams.get('search') ? { search: searchParams.get('search') } : {}),
            ...(searchParams.get('status') ? { status: searchParams.get('status') } : {}),
        };

        try {
            const response = await fetchAdminContactMessages(params);
            setPayload({
                data: response.data ?? [],
                meta: response.meta ?? null,
                links: response.links ?? null,
                stats: response.stats ?? null,
            });
        } catch (err) {
            setError(normalizeApiError(err));
            setPayload({ data: [], meta: null, links: null, stats: null });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, [searchParams]);

    const applyFilters = (event) => {
        event.preventDefault();
        const next = new URLSearchParams();
        if (search) {
            next.set('search', search);
        }
        if (status) {
            next.set('status', status);
        }
        setSearchParams(next);
    };

    const handleStatusChange = async (message, nextStatus) => {
        try {
            await updateAdminContactMessage(message.id, { status: nextStatus });
            setFlash({ type: 'success', message: 'Message updated.' });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const handleDelete = async (message) => {
        if (!window.confirm('Delete this contact message?')) {
            return;
        }

        try {
            await deleteAdminContactMessage(message.id);
            setFlash({ type: 'success', message: 'Message deleted.' });
            if (viewing?.id === message.id) {
                setViewing(null);
            }
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const columns = [
        { key: 'name', label: 'Name', render: (row) => row.name },
        { key: 'email', label: 'Email', render: (row) => row.email },
        { key: 'subject', label: 'Subject', render: (row) => row.subject },
        {
            key: 'status',
            label: 'Status',
            render: (row) => (
                <span className={`rounded-full px-2 py-1 text-xs font-semibold ${statusStyles[row.status] ?? ''}`}>
                    {row.status_label}
                </span>
            ),
        },
        {
            key: 'created_at',
            label: 'Date',
            render: (row) => (row.created_at ? new Date(row.created_at).toLocaleString() : '—'),
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap gap-2">
                    <button type="button" className="text-sm font-medium text-amber-900 hover:underline" onClick={() => setViewing(row)}>
                        View
                    </button>
                    {row.status === 'new' ? (
                        <button type="button" className="text-sm text-stone-600 hover:underline" onClick={() => handleStatusChange(row, 'read')}>
                            Mark read
                        </button>
                    ) : null}
                    {row.status !== 'replied' ? (
                        <button type="button" className="text-sm text-stone-600 hover:underline" onClick={() => handleStatusChange(row, 'replied')}>
                            Mark replied
                        </button>
                    ) : null}
                    {row.status !== 'archived' ? (
                        <button type="button" className="text-sm text-stone-600 hover:underline" onClick={() => handleStatusChange(row, 'archived')}>
                            Archive
                        </button>
                    ) : null}
                    <button type="button" className="text-sm text-red-700 hover:underline" onClick={() => handleDelete(row)}>
                        Delete
                    </button>
                </div>
            ),
        },
    ];

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title="Contact Messages"
                description="Customer messages submitted from the contact page."
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <AdminPanel>
                <AdminPanelBody>
                    <form onSubmit={applyFilters} className="grid gap-4 md:grid-cols-3">
                        <AdminFormField label="Search" name="search" value={search} onChange={(event) => setSearch(event.target.value)} />
                        <AdminSelectField
                            label="Status"
                            name="status"
                            value={status}
                            onChange={(event) => setStatus(event.target.value)}
                            options={statusOptions}
                        />
                        <div className="flex items-end">
                            <button type="submit" className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white">
                                Apply filters
                            </button>
                        </div>
                    </form>
                </AdminPanelBody>
            </AdminPanel>

            <AdminDataTable columns={columns} rows={payload.data} loading={loading} error={error?.message} emptyLabel="No contact messages yet." />

            <AdminPagination meta={payload.meta} links={payload.links} />

            {viewing ? (
                <AdminPanel>
                    <AdminPanelBody className="space-y-4">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-stone-900">{viewing.subject}</h2>
                                <p className="text-sm text-stone-600">
                                    {viewing.name} · {viewing.email}
                                    {viewing.phone ? ` · ${viewing.phone}` : ''}
                                </p>
                            </div>
                            <button type="button" className="text-sm text-stone-600 hover:underline" onClick={() => setViewing(null)}>
                                Close
                            </button>
                        </div>
                        <div className="rounded-md border border-stone-200 bg-stone-50 p-4 text-sm whitespace-pre-wrap text-stone-800">
                            {viewing.message}
                        </div>
                    </AdminPanelBody>
                </AdminPanel>
            ) : null}
        </div>
    );
}
