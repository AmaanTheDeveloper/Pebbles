import { AdminButton } from '../../layouts/AdminLayout';
import { AdminFormField, AdminSelectField } from './AdminFormField';
import { humanizeStatus } from '../../lib/format';

export const ANALYTICS_PRESETS = [
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'last_7_days', label: 'Last 7 days' },
    { value: 'last_30_days', label: 'Last 30 days' },
    { value: 'this_month', label: 'This month' },
    { value: 'last_month', label: 'Last month' },
    { value: 'this_year', label: 'This year' },
    { value: 'custom', label: 'Custom range' },
];

const orderStatuses = ['', 'pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled'];
const paymentStatuses = ['', 'pending', 'submitted', 'verified', 'rejected', 'failed', 'refunded'];
const orderTypes = ['', 'delivery', 'takeaway', 'dine_in'];
const paymentMethods = ['', 'cod', 'easypaisa', 'jazzcash', 'bank_transfer'];

export default function AnalyticsFiltersBar({ value, onChange, branches = [], onApply, applying = false }) {
    const update = (key, nextValue) => {
        onChange({ ...value, [key]: nextValue });
    };

    const isCustom = value.preset === 'custom';

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <AdminSelectField
                label="Date range"
                name="preset"
                value={value.preset ?? 'last_30_days'}
                onChange={(event) => update('preset', event.target.value)}
                options={ANALYTICS_PRESETS}
            />
            {isCustom ? (
                <>
                    <AdminFormField
                        label="From"
                        name="from"
                        type="date"
                        value={value.from ?? ''}
                        onChange={(event) => update('from', event.target.value)}
                        required
                    />
                    <AdminFormField
                        label="To"
                        name="to"
                        type="date"
                        value={value.to ?? ''}
                        onChange={(event) => update('to', event.target.value)}
                        required
                    />
                </>
            ) : null}
            <AdminSelectField
                label="Branch"
                name="branch_id"
                value={value.branch_id ?? ''}
                onChange={(event) => update('branch_id', event.target.value)}
                options={[
                    { value: '', label: 'All branches' },
                    ...branches.map((branch) => ({ value: String(branch.id), label: branch.name })),
                ]}
            />
            <AdminSelectField
                label="Order type"
                name="type"
                value={value.type ?? ''}
                onChange={(event) => update('type', event.target.value)}
                options={orderTypes.map((type) => ({
                    value: type,
                    label: type ? humanizeStatus(type) : 'All types',
                }))}
            />
            <AdminSelectField
                label="Order status"
                name="status"
                value={value.status ?? ''}
                onChange={(event) => update('status', event.target.value)}
                options={orderStatuses.map((status) => ({
                    value: status,
                    label: status ? humanizeStatus(status) : 'All statuses',
                }))}
            />
            <AdminSelectField
                label="Payment status"
                name="payment_status"
                value={value.payment_status ?? ''}
                onChange={(event) => update('payment_status', event.target.value)}
                options={paymentStatuses.map((status) => ({
                    value: status,
                    label: status ? humanizeStatus(status) : 'All payment statuses',
                }))}
            />
            <AdminSelectField
                label="Payment method"
                name="payment_method"
                value={value.payment_method ?? ''}
                onChange={(event) => update('payment_method', event.target.value)}
                options={paymentMethods.map((method) => ({
                    value: method,
                    label: method ? humanizeStatus(method) : 'All methods',
                }))}
            />
            {onApply ? (
                <div className="flex items-end sm:col-span-2 lg:col-span-4">
                    <AdminButton
                        type="button"
                        variant="primary"
                        onClick={onApply}
                        disabled={applying || (isCustom && (!value.from || !value.to))}
                    >
                        {applying ? 'Applying…' : 'Apply filters'}
                    </AdminButton>
                </div>
            ) : null}
        </div>
    );
}
