import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { deleteAdminCoupon, fetchAdminCoupons } from '../../api/admin/coupons';
import AdminDataTable, { AdminPagination } from '../../components/admin/AdminDataTable';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import { AdminPageHeader, AdminPanel } from '../../layouts/AdminLayout';
import { normalizeApiError } from '../../lib/errors';

const statusOptions = [
    { value: '', label: 'All statuses' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'expired', label: 'Expired' },
];

const typeOptions = [
    { value: '', label: 'All types' },
    { value: 'fixed', label: 'Fixed Amount' },
    { value: 'percentage', label: 'Percentage' },
];

const statusStyles = {
    Active: 'bg-emerald-100 text-emerald-900',
    Inactive: 'bg-stone-200 text-stone-600',
    Scheduled: 'bg-sky-100 text-sky-900',
    Expired: 'bg-amber-100 text-amber-900',
    Exhausted: 'bg-rose-100 text-rose-900',
};

function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return `Rs ${Number(value).toLocaleString('en-PK')}`;
}

function formatDate(value) {
    if (!value) {
        return '—';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleString();
}

export default function CouponsPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const [search, setSearch] = useState(searchParams.get('search') ?? '');
    const [status, setStatus] = useState(searchParams.get('status') ?? '');
    const [type, setType] = useState(searchParams.get('type') ?? '');

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = {
            page: searchParams.get('page') ?? '1',
            ...(searchParams.get('search') ? { search: searchParams.get('search') } : {}),
            ...(searchParams.get('status') ? { status: searchParams.get('status') } : {}),
            ...(searchParams.get('type') ? { type: searchParams.get('type') } : {}),
        };

        try {
            const response = await fetchAdminCoupons(params);
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
        if (type) {
            next.set('type', type);
        }
        setSearchParams(next);
    };

    const handleDelete = async (coupon) => {
        if (!window.confirm(`Deactivate/delete coupon "${coupon.code}"?`)) {
            return;
        }

        try {
            await deleteAdminCoupon(coupon.id);
            setFlash({ type: 'success', message: 'Coupon removed.' });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const columns = [
        {
            key: 'code',
            label: 'Code',
            render: (row) => (
                <Link to={`/admin/coupons/${row.id}/edit`} className="font-medium text-amber-900 hover:underline">
                    {row.code}
                </Link>
            ),
        },
        {
            key: 'discount_type',
            label: 'Type',
            render: (row) => row.discount_type_label ?? row.discount_type,
        },
        {
            key: 'discount_value',
            label: 'Value',
            render: (row) =>
                row.discount_type === 'percentage'
                    ? `${Number(row.discount_value)}%`
                    : formatMoney(row.discount_value),
        },
        {
            key: 'minimum_order_amount',
            label: 'Minimum Order',
            render: (row) => formatMoney(row.minimum_order_amount),
        },
        {
            key: 'usage_label',
            label: 'Usage',
            render: (row) => row.usage_label,
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => (
                <span className={`rounded-full px-2 py-0.5 text-xs ${statusStyles[row.status] ?? 'bg-stone-200 text-stone-700'}`}>
                    {row.status}
                </span>
            ),
        },
        {
            key: 'starts_at',
            label: 'Start',
            render: (row) => formatDate(row.starts_at),
        },
        {
            key: 'expires_at',
            label: 'Expiry',
            render: (row) => formatDate(row.expires_at),
        },
        {
            key: 'actions',
            label: '',
            render: (row) => (
                <div className="flex gap-2">
                    <Link to={`/admin/coupons/${row.id}/edit`} className="text-sm text-stone-600 hover:text-stone-900">
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
                title="Coupons"
                description="Create and manage promo codes customers enter manually at checkout."
                actions={
                    <Link
                        to="/admin/coupons/new"
                        className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800"
                    >
                        + Create Coupon
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <AdminPanel>
                <form onSubmit={applyFilters} className="border-b border-stone-200 p-4">
                    <div className="flex max-w-3xl flex-col gap-3 sm:flex-row">
                        <AdminFormField
                            label="Search"
                            name="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Coupon code"
                        />
                        <AdminSelectField
                            label="Status"
                            name="status"
                            value={status}
                            onChange={(event) => setStatus(event.target.value)}
                            options={statusOptions}
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
                    emptyTitle="No coupons found"
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
