import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { fetchAdminBranches } from '../../api/admin/branches';
import { fetchAdminOrders } from '../../api/admin/orders';
import AdminDataTable, { AdminPagination } from '../../components/admin/AdminDataTable';
import { AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import StatusBadge from '../../components/admin/StatusBadge';
import { AdminPageHeader, AdminPanel, adminLinkClassName } from '../../layouts/AdminLayout';
import { formatDate, formatMoney, humanizeStatus } from '../../lib/format';
import { normalizeApiError } from '../../lib/errors';
import { useVisibilityPolling } from '../../hooks/useVisibilityPolling';

const orderStatuses = ['', 'pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled'];
const paymentStatuses = ['', 'pending', 'submitted', 'verified', 'rejected', 'failed', 'refunded'];
const orderTypes = ['', 'delivery', 'takeaway', 'dine_in'];

export default function OrdersPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null });
    const [branches, setBranches] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const filters = useMemo(
        () => ({
            status: searchParams.get('status') ?? '',
            payment_status: searchParams.get('payment_status') ?? '',
            type: searchParams.get('type') ?? '',
            branch_id: searchParams.get('branch_id') ?? '',
            search: searchParams.get('search') ?? '',
            date_from: searchParams.get('date_from') ?? '',
            date_to: searchParams.get('date_to') ?? '',
            sort: searchParams.get('sort') ?? 'newest',
            page: searchParams.get('page') ?? '1',
        }),
        [searchParams],
    );

    const load = useCallback(async ({ silent = false } = {}) => {
        if (!silent) {
            setLoading(true);
        }

        setError(null);

        const params = Object.fromEntries(
            Object.entries(filters).filter(([, value]) => value !== '' && value != null),
        );

        try {
            const response = await fetchAdminOrders(params);
            setPayload({
                data: response.data ?? [],
                meta: response.meta ?? null,
                links: response.links ?? null,
            });
        } catch (err) {
            if (!silent) {
                setError(normalizeApiError(err));
                setPayload({ data: [], meta: null, links: null });
            }
        } finally {
            if (!silent) {
                setLoading(false);
            }
        }
    }, [filters]);

    useEffect(() => {
        fetchAdminBranches({ page: 1 })
            .then((response) => setBranches(response.data ?? []))
            .catch(() => setBranches([]));
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    useVisibilityPolling(
        () => {
            load({ silent: true });
        },
        {
            enabled: true,
            visibleMs: 15000,
            hiddenMs: 40000,
        },
    );

    const updateFilter = (key, value) => {
        const next = new URLSearchParams(searchParams);
        if (value) {
            next.set(key, value);
        } else {
            next.delete(key);
        }
        if (key !== 'page') {
            next.delete('page');
        }
        setSearchParams(next);
    };

    const columns = [
        {
            key: 'order_number',
            label: 'Order',
            render: (row) => (
                <Link to={`/admin/orders/${row.order_number}`} className={adminLinkClassName()}>
                    {row.order_number}
                </Link>
            ),
        },
        {
            key: 'created_at',
            label: 'Placed',
            render: (row) => formatDate(row.created_at),
        },
        {
            key: 'customer',
            label: 'Customer',
            render: (row) => (
                <div>
                    <p className="font-medium">{row.customer?.name ?? '—'}</p>
                    <p className="text-xs text-stone-500">{row.customer?.email ?? ''}</p>
                </div>
            ),
        },
        {
            key: 'branch',
            label: 'Branch',
            render: (row) => row.branch?.name ?? '—',
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            key: 'payment_status',
            label: 'Payment',
            render: (row) => <StatusBadge status={row.payment_status} kind="payment" />,
        },
        {
            key: 'total',
            label: 'Total',
            render: (row) => formatMoney(row.total),
        },
    ];

    return (
        <div className="space-y-6">
            <AdminPageHeader title="Orders" description="Search and manage all customer orders." />

            <AdminPanel>
                <div className="admin-filter-bar">
                    <AdminFormField
                        label="Search order #"
                        name="search"
                        value={filters.search}
                        onChange={(event) => updateFilter('search', event.target.value)}
                    />
                    <AdminSelectField
                        label="Status"
                        name="status"
                        value={filters.status}
                        onChange={(event) => updateFilter('status', event.target.value)}
                        options={orderStatuses.map((status) => ({
                            value: status,
                            label: status ? humanizeStatus(status) : 'All statuses',
                        }))}
                    />
                    <AdminSelectField
                        label="Payment status"
                        name="payment_status"
                        value={filters.payment_status}
                        onChange={(event) => updateFilter('payment_status', event.target.value)}
                        options={paymentStatuses.map((status) => ({
                            value: status,
                            label: status ? humanizeStatus(status) : 'All payments',
                        }))}
                    />
                    <AdminSelectField
                        label="Type"
                        name="type"
                        value={filters.type}
                        onChange={(event) => updateFilter('type', event.target.value)}
                        options={orderTypes.map((type) => ({
                            value: type,
                            label: type ? humanizeStatus(type) : 'All types',
                        }))}
                    />
                    <AdminSelectField
                        label="Branch"
                        name="branch_id"
                        value={filters.branch_id}
                        onChange={(event) => updateFilter('branch_id', event.target.value)}
                        options={[
                            { value: '', label: 'All branches' },
                            ...branches.map((branch) => ({ value: String(branch.id), label: branch.name })),
                        ]}
                    />
                    <AdminFormField
                        label="From date"
                        name="date_from"
                        type="date"
                        value={filters.date_from}
                        onChange={(event) => updateFilter('date_from', event.target.value)}
                    />
                    <AdminFormField
                        label="To date"
                        name="date_to"
                        type="date"
                        value={filters.date_to}
                        onChange={(event) => updateFilter('date_to', event.target.value)}
                    />
                    <AdminSelectField
                        label="Sort"
                        name="sort"
                        value={filters.sort}
                        onChange={(event) => updateFilter('sort', event.target.value)}
                        options={[
                            { value: 'newest', label: 'Newest first' },
                            { value: 'oldest', label: 'Oldest first' },
                            { value: 'highest_total', label: 'Highest total' },
                        ]}
                    />
                </div>

                <AdminDataTable
                    columns={columns}
                    rows={payload.data}
                    rowKey="order_number"
                    loading={loading}
                    error={error}
                    onRetry={load}
                    emptyTitle="No orders found"
                    emptyDescription="Try adjusting your filters."
                    pagination={
                        <AdminPagination
                            meta={payload.meta}
                            links={payload.links}
                            onPageChange={(page) => updateFilter('page', String(page))}
                        />
                    }
                />
            </AdminPanel>
        </div>
    );
}
