import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { fetchAdminPayments } from '../../api/admin/payments';
import AdminDataTable, { AdminPagination } from '../../components/admin/AdminDataTable';
import { AdminFormField, AdminSelectField } from '../../components/admin/AdminFormField';
import StatusBadge from '../../components/admin/StatusBadge';
import { AdminPageHeader, AdminPanel } from '../../layouts/AdminLayout';
import { formatDate, formatMoney, humanizeStatus } from '../../lib/format';
import { normalizeApiError } from '../../lib/errors';

const paymentStatuses = ['', 'pending', 'submitted', 'verified', 'rejected', 'failed', 'refunded'];
const paymentMethods = ['', 'cod', 'easypaisa', 'jazzcash', 'bank_transfer'];

export default function PaymentsPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const filters = useMemo(
        () => ({
            status: searchParams.get('status') ?? '',
            method: searchParams.get('method') ?? '',
            date_from: searchParams.get('date_from') ?? '',
            date_to: searchParams.get('date_to') ?? '',
            page: searchParams.get('page') ?? '1',
        }),
        [searchParams],
    );

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ''));

        try {
            const response = await fetchAdminPayments(params);
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
            key: 'id',
            label: 'Payment',
            render: (row) => (
                <Link to={`/admin/payments/${row.id}`} className="font-medium text-amber-900 hover:underline">
                    #{row.id}
                </Link>
            ),
        },
        {
            key: 'order_number',
            label: 'Order',
            render: (row) =>
                row.order_number ? (
                    <Link to={`/admin/orders/${row.order_number}`} className="text-amber-900 hover:underline">
                        {row.order_number}
                    </Link>
                ) : (
                    '—'
                ),
        },
        {
            key: 'customer',
            label: 'Customer',
            render: (row) => row.customer?.name ?? '—',
        },
        {
            key: 'method',
            label: 'Method',
            render: (row) => humanizeStatus(row.method),
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => <StatusBadge status={row.status} kind="payment" />,
        },
        {
            key: 'amount',
            label: 'Amount',
            render: (row) => formatMoney(row.amount),
        },
        {
            key: 'created_at',
            label: 'Created',
            render: (row) => formatDate(row.created_at),
        },
    ];

    return (
        <div className="space-y-6">
            <AdminPageHeader title="Payments" description="Review submitted proofs and payment status." />

            <AdminPanel>
                <div className="grid gap-4 border-b border-stone-200 p-4 sm:grid-cols-2 lg:grid-cols-4">
                    <AdminSelectField
                        label="Status"
                        name="status"
                        value={filters.status}
                        onChange={(event) => updateFilter('status', event.target.value)}
                        options={paymentStatuses.map((status) => ({
                            value: status,
                            label: status ? humanizeStatus(status) : 'All statuses',
                        }))}
                    />
                    <AdminSelectField
                        label="Method"
                        name="method"
                        value={filters.method}
                        onChange={(event) => updateFilter('method', event.target.value)}
                        options={paymentMethods.map((method) => ({
                            value: method,
                            label: method ? humanizeStatus(method) : 'All methods',
                        }))}
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
                </div>

                <AdminDataTable
                    columns={columns}
                    rows={payload.data}
                    loading={loading}
                    error={error}
                    onRetry={load}
                    emptyTitle="No payments found"
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
