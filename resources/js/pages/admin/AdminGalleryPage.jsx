import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import {
    deleteAdminGalleryItem,
    fetchAdminGalleryItems,
    toggleAdminGalleryItemStatus,
} from '../../api/admin/gallery';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { normalizeApiError } from '../../lib/errors';

const categoryOptions = [
    { value: '', label: 'All categories' },
    { value: 'Food', label: 'Food' },
    { value: 'Interior', label: 'Interior' },
    { value: 'Events', label: 'Events' },
    { value: 'Atmosphere', label: 'Atmosphere' },
];

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

export default function AdminGalleryPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [search, setSearch] = useState(searchParams.get('search') ?? '');
    const [category, setCategory] = useState(searchParams.get('category') ?? '');
    const [status, setStatus] = useState(searchParams.get('status') ?? '');
    const [sort, setSort] = useState(searchParams.get('sort') ?? 'order');

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = {
            page: searchParams.get('page') ?? '1',
            ...(searchParams.get('search') ? { search: searchParams.get('search') } : {}),
            ...(searchParams.get('category') ? { category: searchParams.get('category') } : {}),
            ...(searchParams.get('status') ? { status: searchParams.get('status') } : {}),
            ...(searchParams.get('sort') ? { sort: searchParams.get('sort') } : {}),
        };

        try {
            const response = await fetchAdminGalleryItems(params);
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
        if (category) {
            next.set('category', category);
        }
        if (status) {
            next.set('status', status);
        }
        if (sort) {
            next.set('sort', sort);
        }
        setSearchParams(next);
    };

    const handleDelete = async (item) => {
        if (!window.confirm('Are you sure you want to delete this gallery image?')) {
            return;
        }

        try {
            const response = await deleteAdminGalleryItem(item.id);
            setFlash({ type: 'success', message: response.message || 'Gallery image deleted successfully.' });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const handleToggle = async (item) => {
        try {
            const response = await toggleAdminGalleryItemStatus(item.id, !item.is_active);
            setFlash({ type: 'success', message: response.message });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title="Gallery"
                description="Manage customer gallery images, categories, and display order."
                actions={
                    <Link
                        to="/admin/gallery/new"
                        className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800"
                    >
                        Add gallery item
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <AdminPanel>
                <AdminPanelBody>
                    <form onSubmit={applyFilters} className="grid gap-4 md:grid-cols-5">
                        <AdminFormField label="Search" name="search" value={search} onChange={(event) => setSearch(event.target.value)} />
                        <AdminSelectField label="Category" name="category" value={category} onChange={(event) => setCategory(event.target.value)} options={categoryOptions} />
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

            {loading ? <LoadingState label="Loading gallery items…" /> : null}
            {error ? <ErrorState message={error.message} onRetry={load} /> : null}

            {!loading && !error ? (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {payload.data.map((item) => (
                        <article key={item.id} className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
                            <div className="aspect-[4/3] overflow-hidden bg-stone-100">
                                {item.image?.url ? (
                                    <img src={item.image.url} alt={item.alt_text || item.title} className="size-full object-cover" />
                                ) : (
                                    <div
                                        className={`flex size-full flex-col items-center justify-center bg-gradient-to-br p-6 text-center text-white ${item.gradient ?? 'from-brand-green to-brand-green-light'}`}
                                    >
                                        <p className="font-semibold">{item.title}</p>
                                    </div>
                                )}
                            </div>
                            <div className="space-y-3 p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <h2 className="font-semibold text-stone-900">{item.title}</h2>
                                        <p className="mt-1 text-xs uppercase tracking-wide text-stone-500">{item.category}</p>
                                    </div>
                                    <span
                                        className={[
                                            'rounded-full px-2 py-1 text-xs font-semibold',
                                            item.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700',
                                        ].join(' ')}
                                    >
                                        {item.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </div>
                                <p className="line-clamp-2 text-sm text-stone-600">{item.description || 'No description.'}</p>
                                <p className="text-xs text-stone-500">Order: {item.display_order}</p>
                                <div className="flex flex-wrap gap-2">
                                    <Link to={`/admin/gallery/${item.id}/edit`} className="text-sm font-medium text-amber-900 hover:underline">
                                        Edit
                                    </Link>
                                    <button type="button" onClick={() => handleToggle(item)} className="text-sm text-stone-600 hover:underline">
                                        {item.is_active ? 'Hide' : 'Activate'}
                                    </button>
                                    <button type="button" onClick={() => handleDelete(item)} className="text-sm text-red-700 hover:underline">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            ) : null}

            {!loading && !error && payload.data.length === 0 ? (
                <p className="rounded-xl border border-dashed border-stone-300 bg-white px-6 py-10 text-center text-sm text-stone-600">
                    No gallery items yet. Add your first image to populate the customer gallery.
                </p>
            ) : null}
        </div>
    );
}
