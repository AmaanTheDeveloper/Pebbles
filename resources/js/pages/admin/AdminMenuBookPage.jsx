import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
    deleteAdminMenuBookPage,
    fetchAdminMenuBookPages,
    toggleAdminMenuBookPageStatus,
} from '../../api/admin/menuBook';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { normalizeApiError } from '../../lib/errors';

const statusOptions = [
    { value: '', label: 'All statuses' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const sortOptions = [
    { value: 'order', label: 'Display order' },
    { value: 'newest', label: 'Newest' },
    { value: 'oldest', label: 'Oldest' },
    { value: 'title', label: 'Title' },
];

export default function AdminMenuBookPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [search, setSearch] = useState(searchParams.get('search') ?? '');
    const [status, setStatus] = useState(searchParams.get('status') ?? '');
    const [sort, setSort] = useState(searchParams.get('sort') ?? 'order');
    const [previewPage, setPreviewPage] = useState(null);

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = {
            page: searchParams.get('page') ?? '1',
            ...(searchParams.get('search') ? { search: searchParams.get('search') } : {}),
            ...(searchParams.get('status') ? { status: searchParams.get('status') } : {}),
            ...(searchParams.get('sort') ? { sort: searchParams.get('sort') } : {}),
        };

        try {
            const response = await fetchAdminMenuBookPages(params);
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

    const applyFilters = (event) => {
        event.preventDefault();
        const next = new URLSearchParams();
        if (search) {
            next.set('search', search);
        }
        if (status) {
            next.set('status', status);
        }
        if (sort) {
            next.set('sort', sort);
        }
        setSearchParams(next);
    };

    const handleDelete = async (page) => {
        if (!window.confirm('Are you sure you want to delete this menu page?')) {
            return;
        }

        try {
            const response = await deleteAdminMenuBookPage(page.id);
            setFlash({ type: 'success', message: response.message || 'Menu page deleted successfully.' });
            if (previewPage?.id === page.id) {
                setPreviewPage(null);
            }
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const handleToggle = async (page) => {
        try {
            const response = await toggleAdminMenuBookPageStatus(page.id, !page.is_active);
            setFlash({ type: 'success', message: response.message });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title="Menu Book"
                description="Manage restaurant menu pages shown on the customer menu book."
                actions={
                    <Link
                        to="/admin/menu-book/new"
                        className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800"
                    >
                        Add menu page
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <AdminPanel>
                <AdminPanelBody>
                    <form onSubmit={applyFilters} className="grid gap-4 md:grid-cols-4">
                        <AdminFormField label="Search" name="search" value={search} onChange={(event) => setSearch(event.target.value)} />
                        <AdminSelectField label="Status" name="status" value={status} onChange={(event) => setStatus(event.target.value)} options={statusOptions} />
                        <AdminSelectField label="Sort" name="sort" value={sort} onChange={(event) => setSort(event.target.value)} options={sortOptions} />
                        <div className="flex items-end">
                            <button type="submit" className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white">
                                Apply
                            </button>
                        </div>
                    </form>
                </AdminPanelBody>
            </AdminPanel>

            {loading ? <LoadingState label="Loading menu pages…" /> : null}
            {error ? <ErrorState message={error.message} onRetry={load} /> : null}

            {!loading && !error ? (
                <div className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-stone-200 text-sm">
                            <thead className="bg-stone-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-semibold text-stone-700">Thumbnail</th>
                                    <th className="px-4 py-3 text-left font-semibold text-stone-700">Title</th>
                                    <th className="px-4 py-3 text-left font-semibold text-stone-700">Order</th>
                                    <th className="px-4 py-3 text-left font-semibold text-stone-700">Status</th>
                                    <th className="px-4 py-3 text-left font-semibold text-stone-700">Updated</th>
                                    <th className="px-4 py-3 text-left font-semibold text-stone-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-stone-100">
                                {payload.data.map((page) => (
                                    <tr key={page.id}>
                                        <td className="px-4 py-3">
                                            {page.image?.url ? (
                                                <button type="button" onClick={() => setPreviewPage(page)} className="block overflow-hidden rounded-lg border border-stone-200">
                                                    <img src={page.image.url} alt={page.title || 'Menu page'} className="size-16 object-cover" />
                                                </button>
                                            ) : (
                                                <span className="text-stone-400">No image</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 font-medium text-stone-900">{page.title || `Page ${page.display_order}`}</td>
                                        <td className="px-4 py-3 text-stone-600">{page.display_order}</td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={[
                                                    'rounded-full px-2 py-1 text-xs font-semibold',
                                                    page.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700',
                                                ].join(' ')}
                                            >
                                                {page.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-stone-500">
                                            {page.updated_at ? new Date(page.updated_at).toLocaleDateString() : '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-2">
                                                <Link to={`/admin/menu-book/${page.id}/edit`} className="font-medium text-amber-900 hover:underline">
                                                    Edit
                                                </Link>
                                                <button type="button" onClick={() => setPreviewPage(page)} className="text-stone-600 hover:underline">
                                                    Preview
                                                </button>
                                                <button type="button" onClick={() => handleToggle(page)} className="text-stone-600 hover:underline">
                                                    {page.is_active ? 'Hide' : 'Activate'}
                                                </button>
                                                <button type="button" onClick={() => handleDelete(page)} className="text-red-700 hover:underline">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            ) : null}

            {!loading && !error && payload.data.length === 0 ? (
                <p className="rounded-xl border border-dashed border-stone-300 bg-white px-6 py-10 text-center text-sm text-stone-600">
                    No menu pages yet. Add your first page to populate the restaurant menu book.
                </p>
            ) : null}

            {previewPage?.image?.url ? (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/80 p-4" role="dialog" aria-modal="true" aria-label="Menu page preview">
                    <button type="button" className="absolute inset-0" aria-label="Close preview" onClick={() => setPreviewPage(null)} />
                    <div className="relative z-10 max-h-[90vh] max-w-3xl overflow-auto rounded-xl bg-white p-4 shadow-xl">
                        <button
                            type="button"
                            onClick={() => setPreviewPage(null)}
                            className="absolute top-3 right-3 rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-700 hover:bg-stone-200"
                        >
                            Close
                        </button>
                        <img src={previewPage.image.url} alt={previewPage.title || 'Menu page preview'} className="mx-auto max-h-[75vh] w-full object-contain" />
                        {previewPage.title ? <p className="mt-3 text-center text-sm font-medium text-stone-700">{previewPage.title}</p> : null}
                    </div>
                </div>
            ) : null}
        </div>
    );
}
