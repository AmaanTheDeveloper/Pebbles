import { Link, useLocation, useParams } from 'react-router-dom';
import { useMemo } from 'react';
import OrderSuccessCelebration from '../components/orders/OrderSuccessCelebration';
import PaymentStatusBadge from '../components/orders/PaymentStatusBadge';
import { formatMoney } from '../lib/format';
import { shouldCelebrateNewOrder } from '../lib/orderSuccessCelebration';

export default function OrderConfirmationPage() {
    const { orderNumber } = useParams();
    const location = useLocation();
    const order = location.state?.order;
    const branchName = location.state?.branchName ?? null;
    const resolvedNumber = order?.order_number || orderNumber;
    const requiresProof = order?.payment?.requires_proof;

    const celebrate = useMemo(
        () =>
            shouldCelebrateNewOrder({
                celebrate: Boolean(location.state?.celebrate),
                orderNumber: resolvedNumber,
            }),
        [location.state?.celebrate, resolvedNumber],
    );

    return (
        <div className="mx-auto max-w-lg space-y-6 text-center">
            <div className={['order-success-panel space-y-5', celebrate ? 'order-success-panel-celebrate' : ''].filter(Boolean).join(' ')}>
                <OrderSuccessCelebration active={celebrate} />

                <div className="order-success-copy space-y-3" role="status" aria-live="polite">
                    <p className="text-xs font-semibold tracking-[0.22em] text-brand-green uppercase">
                        Order placed successfully!
                    </p>
                    <h1 className="font-display text-3xl font-semibold text-brand-ink sm:text-4xl">
                        Thank you!
                    </h1>
                    <p className="mx-auto max-w-md text-base leading-relaxed text-brand-muted">
                        Your order has been received and we&apos;re getting it ready.
                    </p>
                    {resolvedNumber ? (
                        <p className="text-lg font-semibold text-brand-green">
                            Order #{resolvedNumber}
                        </p>
                    ) : null}
                    {branchName ? (
                        <p className="text-sm text-brand-muted">{branchName}</p>
                    ) : null}
                    {order?.estimated_minutes ? (
                        <p className="text-sm text-brand-muted">
                            Estimated preparation time: About {order.estimated_minutes} minutes after confirmation
                        </p>
                    ) : null}
                </div>
            </div>

            <div className="rounded-2xl bg-white p-6 text-left ring-1 ring-stone-200">
                <dl className="space-y-3 text-sm">
                    <div className="flex justify-between gap-3">
                        <dt className="text-stone-500">Order number</dt>
                        <dd className="font-semibold text-stone-900">{resolvedNumber}</dd>
                    </div>
                    {order?.fulfillment_type ? (
                        <div className="flex justify-between gap-3">
                            <dt className="text-stone-500">Fulfillment</dt>
                            <dd className="capitalize text-stone-900">
                                {order.fulfillment_type.replace('_', '-')}
                            </dd>
                        </div>
                    ) : null}
                    {order?.status ? (
                        <div className="flex justify-between gap-3">
                            <dt className="text-stone-500">Order status</dt>
                            <dd className="capitalize text-stone-900">{order.status}</dd>
                        </div>
                    ) : null}
                    {order?.payment ? (
                        <div className="flex items-center justify-between gap-3">
                            <dt className="text-stone-500">Payment</dt>
                            <dd className="flex items-center gap-2">
                                <span className="capitalize text-stone-900">{order.payment.method}</span>
                                <PaymentStatusBadge status={order.payment.status} />
                            </dd>
                        </div>
                    ) : null}
                    {order?.total !== undefined ? (
                        <div className="flex justify-between gap-3 border-t border-stone-200 pt-3 text-base">
                            <dt className="font-semibold text-stone-900">Total</dt>
                            <dd className="font-semibold text-amber-950">{formatMoney(order.total)}</dd>
                        </div>
                    ) : null}
                </dl>
            </div>

            <p className="text-sm text-stone-500">
                {requiresProof
                    ? 'This payment method needs a receipt upload before verification.'
                    : 'Payment is not marked successful until staff confirms it when applicable.'}
            </p>

            <div className="flex flex-col gap-2 sm:flex-row sm:justify-center">
                {requiresProof ? (
                    <Link
                        to={`/order/${resolvedNumber}`}
                        className="btn-green inline-flex justify-center px-4 py-2.5 text-sm font-semibold"
                    >
                        Complete payment
                    </Link>
                ) : (
                    <Link
                        to={`/orders/${resolvedNumber}/track`}
                        className="btn-green inline-flex justify-center px-4 py-2.5 text-sm font-semibold"
                    >
                        Track order
                    </Link>
                )}
                <Link
                    to="/menu"
                    className="inline-flex justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-stone-800 ring-1 ring-stone-300 hover:bg-stone-100"
                >
                    Continue shopping
                </Link>
            </div>

            <div className="flex flex-col gap-2 sm:flex-row sm:justify-center">
                <Link
                    to={`/order/${resolvedNumber}`}
                    className="text-sm font-medium text-amber-900 hover:underline"
                >
                    View order details
                </Link>
                <Link to="/orders" className="text-sm font-medium text-amber-900 hover:underline">
                    Order history
                </Link>
            </div>
        </div>
    );
}
