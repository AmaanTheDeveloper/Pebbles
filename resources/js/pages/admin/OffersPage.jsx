import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { deleteAdminOffer, fetchAdminOffers } from '../../api/admin/offers';
import AdminDataTable, { AdminPagination } from '../../components/admin/AdminDataTable';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import { AdminPageHeader, AdminPanel } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const typeOptions = [
    { value: '', label: 'All types' },
    { value: 'combo_offers', label: 'Combo Offers' },
    { value: 'discounts', label: 'Discounts' },
    { value: 'special_events', label: 'Special Events' },
    { value: 'seasonal_deals', label: 'Seasonal Deals' },
];

const typeLabels = {
    combo_offers: 'Combo Offers',
    discounts: 'Discounts',
    special_events: 'Special Events',
    seasonal_deals: 'Seasonal Deals',
};

export default function OffersPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [search, setSearch] = useState(searchParams.get('search') ?? '');
    const [type, setType] = useState(searchParams.get('type') ?? '');

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = {
            page: searchParams.get('page') ?? '1',
            ...(searchParams.get('search') ? { search: searchParams.get('search') } : {}),
            ...(searchParams.get('type') ? { type: searchParams.get('type') } : {}),
        };

        try {
            const response = await fetchAdminOffers(params);
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
        if (type) {
            next.set('type', type);
        }
        setSearchParams(next);
    };

    const handleDelete = async (offer) => {
        if (!window.confirm(`Delete "${offer.title}"?`)) {
            return;
        }

        try {
            await deleteAdminOffer(offer.id);
            setFlash({ type: 'success', message: 'Offer deleted.' });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const columns = [
        {
            key: 'title',
            label: 'Offer',
            render: (row) => (
                <div>
                    <Link to={`/admin/offers/${row.id}/edit`} className="font-medium text-amber-900 hover:underline">
                        {row.title}
                    </Link>
                    {row.offer_value ? <p className="text-xs text-stone-500">{row.offer_value}</p> : null}
                </div>
            ),
        },
        {
            key: 'type',
            label: 'Type',
            render: (row) => typeLabels[row.type] ?? row.type_label ?? row.type,
        },
        {
            key: 'is_active',
            label: 'Status',
            render: (row) => (
                <span
                    className={`rounded-full px-2 py-0.5 text-xs ${row.is_active ? 'bg-emerald-100 text-emerald-900' : 'bg-stone-200 text-stone-600'}`}
                >
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
                    <Link to={`/admin/offers/${row.id}/edit`} className="text-sm text-stone-600 hover:text-stone-900">
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
                title="Offers"
                description="Manage combo offers, discounts, special events, and seasonal deals shown on /offers."
                actions={
                    <Link
                        to="/admin/offers/new"
                        className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800"
                    >
                        New offer
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <AdminPanel>
                <form onSubmit={applyFilters} className="border-b border-stone-200 p-4">
                    <div className="flex max-w-2xl flex-col gap-3 sm:flex-row">
                        <AdminFormField
                            label="Search"
                            name="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                        />
                        <AdminSelectField
                            label="Type"
                            name="type"
                            value={type}
                            onChange={(event) => setType(event.target.value)}
                            options={typeOptions}
                        />
                        <div className="flex items-end">
                            <button
                                type="submit"
                                className="rounded-md border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-50"
                            >
                                Filter
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
                    emptyTitle="No offers found"
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
