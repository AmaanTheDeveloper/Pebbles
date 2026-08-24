import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { deleteAdminAddOnGroup, fetchAdminAddOnGroups } from '../../api/admin/addons';
import AdminDataTable, { AdminPagination } from '../../components/admin/AdminDataTable';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField } from '../../components/admin/AdminFormField';
import { AdminPageHeader, AdminPanel } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

export default function AddOnsPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [search, setSearch] = useState(searchParams.get('search') ?? '');

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = {
            page: searchParams.get('page') ?? '1',
            ...(searchParams.get('search') ? { search: searchParams.get('search') } : {}),
        };

        try {
            const response = await fetchAdminAddOnGroups(params);
            setPayload({
                data: response.data ?? [],
                meta: response.meta ?? null,
                links: response.links ?? null,
            });
        } catch (err) {
            setError(normalizeApiError(err));
            setPayload({ data: [], meta: null, links: null });
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, [searchParams]);

    const applySearch = (event) => {
        event.preventDefault();
        const next = new URLSearchParams();
        if (search) {
            next.set('search', search);
        }
        setSearchParams(next);
    };

    const handleDeactivate = async (group) => {
        if (!window.confirm(`Deactivate "${group.name}"?`)) {
            return;
        }

        try {
            await deleteAdminAddOnGroup(group.id);
            setFlash({ type: 'success', message: 'Add-on group deactivated.' });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const columns = [
        {
            key: 'name',
            label: 'Group',
            render: (row) => (
                <div>
                    <Link to={`/admin/add-ons/${row.id}/edit`} className="font-medium text-amber-900 hover:underline">
                        {row.name}
                    </Link>
                    <p className="text-xs text-stone-500">{row.slug}</p>
                </div>
            ),
        },
        {
            key: 'options',
            label: 'Options',
            render: (row) => row.add_ons?.length ?? 0,
        },
        {
            key: 'rules',
            label: 'Selection',
            render: (row) => (
                <span className="text-sm text-stone-600">
                    {row.is_required ? 'Required' : 'Optional'} · min {row.min_selections ?? 0}
                    {row.max_selections != null ? ` · max ${row.max_selections}` : ''}
                </span>
            ),
        },
        {
            key: 'is_active',
            label: 'Status',
            render: (row) => (
                <span className={`rounded-full px-2 py-0.5 text-xs ${row.is_active ? 'bg-emerald-100 text-emerald-900' : 'bg-stone-200 text-stone-600'}`}>
                    {row.is_active ? 'Active' : 'Inactive'}
                </span>
            ),
        },
        {
            key: 'actions',
            label: '',
            render: (row) => (
                <div className="flex gap-2">
                    <Link to={`/admin/add-ons/${row.id}/edit`} className="text-sm text-stone-600 hover:text-stone-900">
                        Edit
                    </Link>
                    <button type="button" onClick={() => handleDeactivate(row)} className="text-sm text-red-700 hover:underline">
                        Deactivate
                    </button>
                </div>
            ),
        },
    ];

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title="Add-ons"
                description="Manage optional extras and modifier groups."
                actions={
                    <Link
                        to="/admin/add-ons/new"
                        className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800"
                    >
                        New group
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <AdminPanel>
                <form onSubmit={applySearch} className="border-b border-stone-200 p-4">
                    <div className="flex max-w-md gap-3">
                        <AdminFormField label="Search" name="search" value={search} onChange={(event) => setSearch(event.target.value)} />
                        <div className="flex items-end">
                            <button type="submit" className="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-50">
                                Search
                            </button>
                        </div>
                    </div>
                </form>

                <AdminDataTable
                    columns={columns}
                    rows={payload.data}
                    loading={loading}
                    error={error}
                    onRetry={load}
                    emptyTitle="No add-on groups found"
                    pagination={
                        <AdminPagination
                            meta={payload.meta}
                            links={payload.links}
                            onPageChange={(page) => {
                                const next = new URLSearchParams(searchParams);
                                next.set('page', String(page));
                                setSearchParams(next);
                            }}
                        />
                    }
                />
            </AdminPanel>
        </div>
    );
}
