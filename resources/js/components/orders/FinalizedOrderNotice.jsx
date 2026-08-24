import { Link } from 'react-router-dom';
import { getCustomerStatusLabel, isFinalOrder } from '../../lib/orderTracking';

export default function FinalizedOrderNotice({ order }) {
    if (!isFinalOrder(order)) {
        return null;
    }

    const label = getCustomerStatusLabel(order);
    const isCancelled = order.status === 'cancelled';

    return (
        <div className="rounded-[1.75rem] bg-white p-5 ring-1 ring-brand-green/10 sm:p-6">
            <h2 className="font-display text-xl font-semibold text-brand-ink">
                {isCancelled ? 'Order Cancelled' : 'Order Completed'}
            </h2>
            <p className="mt-2 text-sm text-brand-muted">
                {isCancelled
                    ? 'This order was cancelled and is now available in Recent Orders.'
                    : 'This order has been completed and is now available in Recent Orders.'}
            </p>
            <p className="mt-3 text-sm font-medium text-brand-ink">{label}</p>
            {order?.lifecycle?.cancellation_reason ? (
                <p className="mt-2 text-sm text-brand-muted">
                    Reason: {order.lifecycle.cancellation_reason}
                </p>
            ) : null}
            <Link
                to={`/order/${order.order_number}`}
                className="contact-secondary-btn mt-4 inline-flex justify-center"
            >
                View order details
            </Link>
        </div>
    );
}
