import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { deleteAdminCategory, fetchAdminCategories } from '../../api/admin/categories';
import AdminDataTable, { AdminPagination } from '../../components/admin/AdminDataTable';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField } from '../../components/admin/AdminFormField';
import { AdminPageHeader, AdminPanel } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

export default function CategoriesPage() {
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
            const response = await fetchAdminCategories(params);
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

    const handleDelete = async (category) => {
        if (!window.confirm(`Delete "${category.name}"?`)) {
            return;
        }

        try {
            await deleteAdminCategory(category.id);
            setFlash({ type: 'success', message: 'Category deleted.' });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const columns = [
        {
            key: 'name',
            label: 'Category',
            render: (row) => (
                <div>
                    <Link to={`/admin/categories/${row.id}/edit`} className="font-medium text-amber-900 hover:underline">
                        {row.name}
                    </Link>
                    <p className="text-xs text-stone-500">{row.slug}</p>
                </div>
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
            key: 'sort_order',
            label: 'Sort',
        },
        {
            key: 'actions',
            label: '',
            render: (row) => (
                <div className="flex gap-2">
                    <Link to={`/admin/categories/${row.id}/edit`} className="text-sm text-stone-600 hover:text-stone-900">
                        Edit
                    </Link>
                    <button type="button" onClick={() => handleDelete(row)} className="text-sm text-red-700 hover:underline">
                        Delete
                    </button>
                </div>
            ),
        },
    ];

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title="Categories"
                description="Organize products into menu categories."
                actions={
                    <Link
                        to="/admin/categories/new"
                        className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800"
                    >
                        New category
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
                    emptyTitle="No categories found"
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
