import { formatDate, formatMoney, humanizeStatus } from '../../lib/format';
import { formatFulfillmentType } from '../../lib/orderTracking';

export default function OrderTrackingSummary({ order }) {
    if (!order) {
        return null;
    }

    const fulfillment = order.fulfillment ?? {};
    const itemCount = (order.items ?? []).reduce((total, item) => total + Number(item.quantity ?? 0), 0);

    return (
        <div className="rounded-[1.75rem] bg-white p-5 ring-1 ring-brand-green/10 sm:p-6">
            <h2 className="font-display text-lg font-semibold text-brand-ink">Order Summary</h2>
            <dl className="mt-4 space-y-3 text-sm">
                <div className="flex justify-between gap-4">
                    <dt className="text-brand-muted">Order number</dt>
                    <dd className="font-medium text-brand-ink">{order.order_number}</dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-brand-muted">Order type</dt>
                    <dd className="font-medium text-brand-ink">{formatFulfillmentType(order.fulfillment_type)}</dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-brand-muted">Items</dt>
                    <dd className="font-medium text-brand-ink">{itemCount}</dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-brand-muted">Total</dt>
                    <dd className="font-semibold text-brand-green">{formatMoney(order.total)}</dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-brand-muted">Placed</dt>
                    <dd className="font-medium text-brand-ink">{formatDate(order.created_at)}</dd>
                </div>
                <div className="flex justify-between gap-4">
                    <dt className="text-brand-muted">Status</dt>
                    <dd className="font-medium capitalize text-brand-ink">{humanizeStatus(order.status)}</dd>
                </div>
            </dl>

            {fulfillment.delivery_line1 ? (
                <div className="mt-4 rounded-xl bg-brand-cream/70 p-4">
                    <p className="text-xs font-semibold tracking-wide text-brand-green uppercase">Delivery address</p>
                    <p className="mt-2 text-sm text-brand-ink">
                        {[fulfillment.delivery_recipient_name, fulfillment.delivery_phone].filter(Boolean).join(' · ')}
                    </p>
                    <p className="mt-1 text-sm text-brand-muted">
                        {[fulfillment.delivery_line1, fulfillment.delivery_line2].filter(Boolean).join(', ')}
                    </p>
                    <p className="text-sm text-brand-muted">
                        {[fulfillment.delivery_area, fulfillment.delivery_city].filter(Boolean).join(', ')}
                    </p>
                </div>
            ) : null}

            {order.branch?.name && !fulfillment.delivery_line1 ? (
                <div className="mt-4 rounded-xl bg-brand-cream/70 p-4">
                    <p className="text-xs font-semibold tracking-wide text-brand-green uppercase">Pickup location</p>
                    <p className="mt-2 text-sm font-medium text-brand-ink">{order.branch.name}</p>
                    {order.branch.address ? <p className="mt-1 text-sm text-brand-muted">{order.branch.address}</p> : null}
                </div>
            ) : null}
        </div>
    );
}

export function OrderTrackingSkeleton() {
    return (
        <div className="space-y-6 animate-pulse">
            <div className="h-28 rounded-[1.75rem] bg-stone-200/70" />
            <div className="h-44 rounded-[1.75rem] bg-stone-200/70" />
            <div className="h-72 rounded-[1.75rem] bg-stone-200/70" />
            <div className="h-52 rounded-[1.75rem] bg-stone-200/70" />
        </div>
    );
}
