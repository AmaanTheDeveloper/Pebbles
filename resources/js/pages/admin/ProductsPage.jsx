import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { fetchAdminCategories } from '../../api/admin/categories';
import { deleteAdminProduct, fetchAdminProducts } from '../../api/admin/products';
import AdminDataTable, { AdminPagination } from '../../components/admin/AdminDataTable';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import { AdminPageHeader, AdminPanel } from '../../layouts/AdminLayout';
import { formatMoney } from '../../lib/format';
import { normalizeApiError } from '../../lib/errors';

export default function ProductsPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null });
    const [categories, setCategories] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [search, setSearch] = useState(searchParams.get('search') ?? '');
    const [categoryId, setCategoryId] = useState(searchParams.get('category_id') ?? '');

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = {
            page: searchParams.get('page') ?? '1',
            ...(searchParams.get('search') ? { search: searchParams.get('search') } : {}),
            ...(searchParams.get('category_id') ? { category_id: searchParams.get('category_id') } : {}),
        };

        try {
            const response = await fetchAdminProducts(params);
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
        fetchAdminCategories()
            .then((response) => setCategories(response.data ?? []))
            .catch(() => setCategories([]));
    }, []);

    useEffect(() => {
        load();
    }, [searchParams]);

    const applyFilters = (event) => {
        event.preventDefault();
        const next = new URLSearchParams();
        if (search) {
            next.set('search', search);
        }
        if (categoryId) {
            next.set('category_id', categoryId);
        }
        setSearchParams(next);
    };

    const handleDeactivate = async (product) => {
        if (!window.confirm(`Deactivate "${product.name}"?`)) {
            return;
        }

        try {
            await deleteAdminProduct(product.id);
            setFlash({ type: 'success', message: 'Product deactivated.' });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const columns = [
        {
            key: 'thumb',
            label: '',
            render: (row) => (
                <div className="size-12 overflow-hidden rounded-md bg-stone-100 ring-1 ring-stone-200">
                    {row.primary_image?.url ? (
                        <img
                            src={row.primary_image.url}
                            alt={row.primary_image.alt_text || row.name}
                            className="size-full object-cover"
                        />
                    ) : (
                        <div className="flex size-full items-center justify-center text-[10px] text-stone-400">N/A</div>
                    )}
                </div>
            ),
        },
        {
            key: 'name',
            label: 'Product',
            render: (row) => (
                <div>
                    <Link to={`/admin/products/${row.id}/edit`} className="font-medium text-amber-900 hover:underline">
                        {row.name}
                    </Link>
                    <p className="text-xs text-stone-500">{row.slug}</p>
                </div>
            ),
        },
        {
            key: 'category',
            label: 'Category',
            render: (row) => row.category?.name ?? '—',
        },
        {
            key: 'base_price',
            label: 'Base price',
            render: (row) => formatMoney(row.base_price),
        },
        {
            key: 'flags',
            label: 'Status',
            render: (row) => (
                <div className="flex flex-wrap gap-1 text-xs">
                    <span className={`rounded-full px-2 py-0.5 ${row.is_active ? 'bg-emerald-100 text-emerald-900' : 'bg-stone-200 text-stone-600'}`}>
                        {row.is_active ? 'Active' : 'Inactive'}
                    </span>
                    <span className={`rounded-full px-2 py-0.5 ${row.is_available ? 'bg-sky-100 text-sky-900' : 'bg-stone-200 text-stone-600'}`}>
                        {row.is_available ? 'Available' : 'Unavailable'}
                    </span>
                </div>
            ),
        },
        {
            key: 'actions',
            label: '',
            render: (row) => (
                <div className="flex gap-2">
                    <Link to={`/admin/products/${row.id}/edit`} className="text-sm text-stone-600 hover:text-stone-900">
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
                title="Products"
                description="Manage menu items, variants, and images."
                actions={
                    <Link
                        to="/admin/products/new"
                        className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800"
                    >
                        New product
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <AdminPanel>
                <form onSubmit={applyFilters} className="grid gap-4 border-b border-stone-200 p-4 sm:grid-cols-3">
                    <AdminFormField label="Search" name="search" value={search} onChange={(event) => setSearch(event.target.value)} />
                    <AdminSelectField
                        label="Category"
                        name="category_id"
                        value={categoryId}
                        onChange={(event) => setCategoryId(event.target.value)}
                        options={[
                            { value: '', label: 'All categories' },
                            ...categories.map((category) => ({ value: String(category.id), label: category.name })),
                        ]}
                    />
                    <div className="flex items-end">
                        <button type="submit" className="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-50">
                            Apply filters
                        </button>
                    </div>
                </form>

                <AdminDataTable
                    columns={columns}
                    rows={payload.data}
                    loading={loading}
                    error={error}
                    onRetry={load}
                    emptyTitle="No products found"
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
