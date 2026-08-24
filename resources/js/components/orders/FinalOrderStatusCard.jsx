import { getCustomerStatusLabel } from '../../lib/orderTracking';

export default function FinalOrderStatusCard({ order }) {
    const label = getCustomerStatusLabel(order);
    const isCancelled = order?.status === 'cancelled';
    const isCompleted = order?.status === 'completed';

    return (
        <div
            className={[
                'rounded-2xl px-4 py-4 ring-1',
                isCancelled ? 'bg-red-50 ring-red-200' : 'bg-emerald-50 ring-emerald-200',
            ].join(' ')}
        >
            <p
                className={[
                    'text-sm font-semibold',
                    isCancelled ? 'text-red-800' : 'text-emerald-800',
                ].join(' ')}
            >
                {isCompleted ? '✓ ' : ''}
                {label}
            </p>
            {order?.lifecycle?.cancellation_reason ? (
                <div className="mt-2 text-sm text-stone-700">
                    <p className="font-medium text-stone-800">Reason</p>
                    <p>{order.lifecycle.cancellation_reason}</p>
                </div>
            ) : null}
            {order?.lifecycle?.finalized_at ? (
                <p className="mt-2 text-xs text-stone-500">
                    Finalized {new Date(order.lifecycle.finalized_at).toLocaleString('en-PK')}
                </p>
            ) : null}
        </div>
    );
}
