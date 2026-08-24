import { Link } from 'react-router-dom';
import { formatDate, formatMoney } from '../../lib/format';
import { getCustomerStatusLabel, isActiveOrder } from '../../lib/orderTracking';
import PaymentStatusBadge from './PaymentStatusBadge';
import ReorderOrderButton from './ReorderOrderButton';

export default function OrderCard({ order, variant = 'auto' }) {
    const active = variant === 'active' || (variant === 'auto' && isActiveOrder(order));
    const statusLabel = getCustomerStatusLabel(order);

    return (
        <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200 transition hover:ring-amber-800/40">
            <Link to={`/order/${order.order_number}`} className="block">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <p className="font-semibold text-stone-900">{order.order_number}</p>
                        <p className="mt-1 text-sm text-stone-500">
                            {formatDate(active ? order.created_at : order.lifecycle?.finalized_at ?? order.created_at)}
                        </p>
                    </div>
                    <div className="text-right">
                        <p
                            className={[
                                'text-sm font-medium',
                                order.status === 'cancelled' ? 'text-red-700' : 'text-stone-800',
                            ].join(' ')}
                        >
                            {statusLabel}
                        </p>
                        <p className="mt-1 font-semibold text-amber-950">{formatMoney(order.total)}</p>
                    </div>
                </div>
                <div className="mt-3 flex flex-wrap items-center gap-2 text-xs text-stone-600">
                    <span className="capitalize">{order.fulfillment_type?.replaceAll('_', ' ')}</span>
                    {order.branch?.name ? <span>· {order.branch.name}</span> : null}
                    <PaymentStatusBadge status={order.payment_status} />
                </div>
            </Link>
            <div className="mt-3 flex flex-wrap items-center gap-3">
                {active ? (
                    <Link
                        to={`/orders/${order.order_number}/track`}
                        className="inline-flex text-sm font-medium text-brand-green hover:text-brand-orange"
                    >
                        Track order →
                    </Link>
                ) : (
                    <>
                        <Link
                            to={`/order/${order.order_number}`}
                            className="inline-flex text-sm font-medium text-stone-700 hover:text-stone-900"
                        >
                            View details
                        </Link>
                        <ReorderOrderButton
                            orderNumber={order.order_number}
                            variant="secondary"
                            className="!px-3 !py-1.5"
                        />
                    </>
                )}
            </div>
        </div>
    );
}
