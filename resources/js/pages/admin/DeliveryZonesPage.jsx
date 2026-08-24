import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { fetchAdminBranches } from '../../api/admin/branches';
import { deleteAdminDeliveryZone, fetchAdminDeliveryZones } from '../../api/admin/deliveryZones';
import AdminDataTable, { AdminPagination } from '../../components/admin/AdminDataTable';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminSelectField } from '../../components/admin/AdminFormField';
import { AdminPageHeader, AdminPanel } from '../../layouts/AdminLayout';
import { formatMoney } from '../../lib/format';
import { normalizeApiError } from '../../lib/errors';

export default function DeliveryZonesPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const [payload, setPayload] = useState({ data: [], meta: null, links: null });
    const [branches, setBranches] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [flash, setFlash] = useState(null);
    const branchId = searchParams.get('branch_id') ?? '';

    const load = async () => {
        setLoading(true);
        setError(null);

        const params = {
            page: searchParams.get('page') ?? '1',
            ...(branchId ? { branch_id: branchId } : {}),
        };

        try {
            const response = await fetchAdminDeliveryZones(params);
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
        fetchAdminBranches()
            .then((response) => setBranches(response.data ?? []))
            .catch(() => setBranches([]));
    }, []);

    useEffect(() => {
        load();
    }, [searchParams]);

    const updateBranchFilter = (value) => {
        const next = new URLSearchParams(searchParams);
        if (value) {
            next.set('branch_id', value);
        } else {
            next.delete('branch_id');
        }
        next.delete('page');
        setSearchParams(next);
    };

    const handleDeactivate = async (zone) => {
        if (!window.confirm(`Deactivate "${zone.name}"?`)) {
            return;
        }

        try {
            await deleteAdminDeliveryZone(zone.id);
            setFlash({ type: 'success', message: 'Delivery zone deactivated.' });
            load();
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        }
    };

    const columns = [
        {
            key: 'name',
            label: 'Zone',
            render: (row) => (
                <Link to={`/admin/delivery-zones/${row.id}/edit`} className="font-medium text-amber-900 hover:underline">
                    {row.name}
                </Link>
            ),
        },
        {
            key: 'branch',
            label: 'Branch',
            render: (row) => row.branch?.name ?? '—',
        },
        {
            key: 'delivery_fee',
            label: 'Fee',
            render: (row) => formatMoney(row.delivery_fee),
        },
        {
            key: 'minimum_order_amount',
            label: 'Min order',
            render: (row) => (row.minimum_order_amount != null ? formatMoney(row.minimum_order_amount) : '—'),
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
                    <Link to={`/admin/delivery-zones/${row.id}/edit`} className="text-sm text-stone-600 hover:text-stone-900">
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
                title="Delivery zones"
                description="Configure delivery fees and coverage by branch."
                actions={
                    <Link
                        to="/admin/delivery-zones/new"
                        className="rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800"
                    >
                        New zone
                    </Link>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <AdminPanel>
                <div className="border-b border-stone-200 p-4">
                    <div className="max-w-xs">
                        <AdminSelectField
                            label="Branch"
                            name="branch_id"
                            value={branchId}
                            onChange={(event) => updateBranchFilter(event.target.value)}
                            options={[
                                { value: '', label: 'All branches' },
                                ...branches.map((branch) => ({ value: String(branch.id), label: branch.name })),
                            ]}
                        />
                    </div>
                </div>

                <AdminDataTable
                    columns={columns}
                    rows={payload.data}
                    loading={loading}
                    error={error}
                    onRetry={load}
                    emptyTitle="No delivery zones found"
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
