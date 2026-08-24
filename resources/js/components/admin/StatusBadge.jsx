import { humanizeStatus } from '../../lib/format';

const orderStyles = {
    pending: 'bg-amber-100 text-amber-900 ring-1 ring-amber-200/80',
    confirmed: 'bg-sky-100 text-sky-900 ring-1 ring-sky-200/80',
    preparing: 'bg-orange-100 text-orange-900 ring-1 ring-orange-200/80',
    ready: 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-200/80',
    out_for_delivery: 'bg-indigo-100 text-indigo-900 ring-1 ring-indigo-200/80',
    completed: 'bg-stone-200 text-stone-800 ring-1 ring-stone-300/70',
    cancelled: 'bg-red-100 text-red-800 ring-1 ring-red-200/80',
};

const paymentStyles = {
    pending: 'bg-amber-100 text-amber-900 ring-1 ring-amber-200/80',
    submitted: 'bg-sky-100 text-sky-900 ring-1 ring-sky-200/80',
    verified: 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-200/80',
    rejected: 'bg-red-100 text-red-800 ring-1 ring-red-200/80',
    failed: 'bg-red-100 text-red-800 ring-1 ring-red-200/80',
    refunded: 'bg-stone-200 text-stone-700 ring-1 ring-stone-300/70',
};

export default function StatusBadge({ status, kind = 'order' }) {
    const palette = kind === 'payment' ? paymentStyles : orderStyles;
    const className = palette[status] ?? 'bg-stone-100 text-stone-700 ring-1 ring-stone-200/80';

    return (
        <span className={`admin-status-badge ${className}`}>
            {humanizeStatus(status)}
        </span>
    );
}
