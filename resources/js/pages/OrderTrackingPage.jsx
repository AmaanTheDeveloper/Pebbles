import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { fetchOrder } from '../api/orders';
import EmptyState from '../components/storefront/EmptyState';
import ErrorState from '../components/storefront/ErrorState';
import { OrderTrackingStatusCard, OrderTrackingTimeline } from '../components/orders/OrderTrackingTimeline';
import OrderTrackingEstimatePanel from '../components/orders/OrderTrackingEstimatePanel';
import OrderTrackingSummary, { OrderTrackingSkeleton } from '../components/orders/OrderTrackingSummary';
import OrderCustomerActions from '../components/orders/OrderCustomerActions';
import FinalizedOrderNotice from '../components/orders/FinalizedOrderNotice';
import { formatDate, humanizeStatus } from '../lib/format';
import { formatFulfillmentType, formatRelativeUpdate, isFinalOrder, shouldAutoRefresh } from '../lib/orderTracking';
import { useActiveOrders } from '../contexts/ActiveOrdersContext';
import { useVisibilityPolling } from '../hooks/useVisibilityPolling';
import { normalizeApiError } from '../lib/errors';

const AUTO_REFRESH_VISIBLE_MS = 8000;
const AUTO_REFRESH_HIDDEN_MS = 30000;

export default function OrderTrackingPage() {
    const { orderNumber } = useParams();
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState(null);
    const [lastUpdated, setLastUpdated] = useState(null);
    const { refreshActiveOrders } = useActiveOrders();

    const load = useCallback(
        async ({ silent = false } = {}) => {
            if (!silent) {
                setLoading(true);
            } else {
                setRefreshing(true);
            }

            setError(null);

            try {
                const nextOrder = await fetchOrder(orderNumber);
                setOrder(nextOrder);
                setLastUpdated(new Date());

                if (isFinalOrder(nextOrder)) {
                    await refreshActiveOrders();
                }
            } catch (err) {
                setError(normalizeApiError(err));
                if (!silent) {
                    setOrder(null);
                }
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [orderNumber, refreshActiveOrders],
    );

    useEffect(() => {
        load();
        document.title = `Track order ${orderNumber} — Pebbles`;
    }, [load, orderNumber]);

    useVisibilityPolling(
        () => {
            load({ silent: true });
        },
        {
            enabled: Boolean(order && shouldAutoRefresh(order.status)),
            visibleMs: AUTO_REFRESH_VISIBLE_MS,
            hiddenMs: AUTO_REFRESH_HIDDEN_MS,
        },
    );

    if (loading) {
        return (
            <div className="mx-auto max-w-4xl">
                <OrderTrackingSkeleton />
            </div>
        );
    }

    if (error) {
        if (error.status === 403 || error.status === 404) {
            return (
                <EmptyState
                    title="Order not found"
                    description="This order is unavailable or does not belong to your account."
                />
            );
        }

        return (
            <div className="mx-auto max-w-4xl space-y-4">
                <ErrorState
                    message="We couldn't retrieve the latest order status. Please try again."
                    onRetry={() => load()}
                />
            </div>
        );
    }

    if (!order) {
        return (
            <EmptyState
                title="Order not found"
                description="This order is unavailable or does not belong to your account."
            />
        );
    }

    const finalized = isFinalOrder(order);

    if (finalized) {
        return (
            <div className="mx-auto max-w-4xl space-y-6">
                <div>
                    <Link to="/orders" className="text-sm font-medium text-brand-green hover:text-brand-orange">
                        ← All orders
                    </Link>
                    <h1 className="mt-3 font-display text-3xl font-semibold text-brand-ink">{order.order_number}</h1>
                </div>
                <FinalizedOrderNotice order={order} />
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-4xl space-y-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <Link to="/orders" className="text-sm font-medium text-brand-green hover:text-brand-orange">
                        ← All orders
                    </Link>
                    <p className="mt-3 text-xs font-semibold tracking-wide text-brand-orange uppercase">Order Tracking</p>
                    <h1 className="mt-2 font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                        {order.order_number}
                    </h1>
                    <div className="mt-3 flex flex-wrap items-center gap-2 text-sm text-brand-muted">
                        <span className="rounded-full bg-brand-green/10 px-3 py-1 font-medium text-brand-green">
                            {formatFulfillmentType(order.fulfillment_type)}
                        </span>
                        <span className="rounded-full bg-white px-3 py-1 font-medium capitalize text-brand-ink ring-1 ring-brand-green/10">
                            {humanizeStatus(order.status)}
                        </span>
                        <span>{formatDate(order.created_at)}</span>
                    </div>
                </div>

                <div className="flex flex-col items-stretch gap-2 sm:items-end">
                    <Link
                        to={`/order/${order.order_number}`}
                        className="contact-secondary-btn justify-center text-center"
                    >
                        Full order details
                    </Link>
                    <button
                        type="button"
                        onClick={() => load({ silent: true })}
                        disabled={refreshing}
                        className="contact-secondary-btn justify-center disabled:opacity-60"
                    >
                        {refreshing ? 'Refreshing…' : 'Refresh status'}
                    </button>
                    {lastUpdated ? (
                        <p className="text-xs text-brand-muted">{formatRelativeUpdate(lastUpdated)}</p>
                    ) : null}
                </div>
            </div>

            <OrderTrackingEstimatePanel order={order} />

            <OrderTrackingStatusCard
                currentStatus={order.status}
                fulfillmentType={order.fulfillment_type}
            />

            <OrderCustomerActions order={order} onChanged={setOrder} />

            {order.status === 'cancelled' ? (
                <div className="rounded-[1.75rem] bg-white p-5 ring-1 ring-brand-green/10 sm:p-6">
                    <h2 className="font-display text-lg font-semibold text-brand-ink">Status history</h2>
                    <ul className="mt-4 space-y-2 text-sm text-brand-muted">
                        {(order.status_history ?? []).map((entry, index) => (
                            <li key={`${entry.status}-${index}`}>
                                {humanizeStatus(entry.status)} · {formatDate(entry.created_at)}
                            </li>
                        ))}
                    </ul>
                </div>
            ) : (
                <OrderTrackingTimeline
                    currentStatus={order.status}
                    fulfillmentType={order.fulfillment_type}
                    history={order.status_history ?? []}
                />
            )}

            <OrderTrackingSummary order={order} />
        </div>
    );
}
