import { useCallback, useEffect, useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import {
    fetchOrder,
    fetchPaymentMethods,
    paymentProofUrl,
    selectOrderPayment,
    uploadPaymentProof,
} from '../api/orders';
import EmptyState from '../components/storefront/EmptyState';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import OrderItems from '../components/orders/OrderItems';
import OrderStatusTimeline from '../components/orders/OrderStatusTimeline';
import FinalOrderStatusCard from '../components/orders/FinalOrderStatusCard';
import OrderSummary from '../components/orders/OrderSummary';
import PaymentInstructions from '../components/orders/PaymentInstructions';
import PaymentProofUpload from '../components/orders/PaymentProofUpload';
import PaymentStatusBadge from '../components/orders/PaymentStatusBadge';
import OrderCustomerActions from '../components/orders/OrderCustomerActions';
import ReorderOrderButton from '../components/orders/ReorderOrderButton';
import { normalizeApiError } from '../lib/errors';
import { formatDate, humanizeStatus } from '../lib/format';
import { getCustomerStatusLabel, isActiveOrder } from '../lib/orderTracking';
import api from '../api/client';

export default function OrderDetailPage() {
    const { orderNumber } = useParams();
    const location = useLocation();
    const [order, setOrder] = useState(null);
    const [methods, setMethods] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [actionError, setActionError] = useState(null);
    const [flash, setFlash] = useState(() => location.state?.flash ?? null);
    const [busy, setBusy] = useState(false);
    const [selectedMethod, setSelectedMethod] = useState('');

    useEffect(() => {
        if (location.state?.flash) {
            setFlash(location.state.flash);
            window.history.replaceState({}, document.title);
        }
    }, [location.state]);

    const load = useCallback(async () => {
        setLoading(true);
        setError(null);

        try {
            const nextOrder = await fetchOrder(orderNumber);
            setOrder(nextOrder);

            if (isActiveOrder(nextOrder)) {
                const nextMethods = await fetchPaymentMethods();
                setMethods(nextMethods ?? []);
                setSelectedMethod(nextOrder?.payment?.method || nextMethods?.[0]?.id || '');
            } else {
                setMethods([]);
                setSelectedMethod('');
            }
        } catch (err) {
            setError(normalizeApiError(err));
            setOrder(null);
        } finally {
            setLoading(false);
        }
    }, [orderNumber]);

    useEffect(() => {
        load();
    }, [load]);

    const handleSelectMethod = async (event) => {
        event.preventDefault();
        if (!selectedMethod || busy) {
            return;
        }

        setBusy(true);
        setActionError(null);

        try {
            const updated = await selectOrderPayment(orderNumber, { method: selectedMethod });
            setOrder(updated);
        } catch (err) {
            setActionError(normalizeApiError(err).message);
        } finally {
            setBusy(false);
        }
    };

    const handleUpload = async ({ file, transaction_reference }) => {
        setActionError(null);
        const updated = await uploadPaymentProof(orderNumber, { file, transaction_reference });
        setOrder(updated);
    };

    const handleViewProof = async () => {
        if (!order?.payment?.proof?.id) {
            return;
        }

        try {
            const response = await api.get(
                paymentProofUrl(order.order_number, order.payment.proof.id),
                { responseType: 'blob' },
            );
            const url = URL.createObjectURL(response.data);
            window.open(url, '_blank', 'noopener,noreferrer');
        } catch (err) {
            setActionError(normalizeApiError(err).message);
        }
    };

    if (loading) {
        return <LoadingState label="Loading order…" />;
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

        return <ErrorState message={error.message} onRetry={load} />;
    }

    if (!order) {
        return (
            <EmptyState
                title="Order not found"
                description="This order is unavailable or does not belong to your account."
            />
        );
    }

    const payment = order.payment;
    const fulfillment = order.fulfillment ?? {};
    const active = isActiveOrder(order);

    return (
        <div className="space-y-6">
            {flash ? (
                <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="status">
                    {flash}
                </div>
            ) : null}
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <Link to="/orders" className="text-sm font-medium text-amber-900 hover:underline">
                        ← All orders
                    </Link>
                    <h1 className="mt-2 text-2xl font-semibold text-stone-900">{order.order_number}</h1>
                    <p className="text-sm text-stone-500">{formatDate(order.created_at)}</p>
                </div>
                <div className="text-right">
                    <p className="text-sm font-medium text-stone-800">
                        {getCustomerStatusLabel(order)}
                    </p>
                    <div className="mt-2 flex justify-end">
                        <PaymentStatusBadge status={payment?.status} />
                    </div>
                    {!active ? (
                        <div className="mt-3">
                            <ReorderOrderButton orderNumber={order.order_number} />
                        </div>
                    ) : null}
                </div>
            </div>

            <div className="grid gap-4 lg:grid-cols-[1.3fr_1fr]">
                <div className="space-y-4">
                    <OrderItems items={order.items ?? []} />
                    <OrderSummary order={order} />

                    <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200">
                        <h2 className="font-semibold text-stone-900">Fulfillment</h2>
                        <dl className="mt-3 space-y-2 text-sm text-stone-600">
                            <div className="flex justify-between gap-3">
                                <dt>Type</dt>
                                <dd className="capitalize text-stone-900">
                                    {humanizeStatus(order.fulfillment_type)}
                                </dd>
                            </div>
                            {order.branch?.name ? (
                                <div className="flex justify-between gap-3">
                                    <dt>Branch</dt>
                                    <dd className="text-stone-900">{order.branch.name}</dd>
                                </div>
                            ) : null}
                            {fulfillment.table_number ? (
                                <div className="flex justify-between gap-3">
                                    <dt>Table</dt>
                                    <dd className="text-stone-900">{fulfillment.table_number}</dd>
                                </div>
                            ) : null}
                            {fulfillment.delivery_line1 ? (
                                <div>
                                    <dt className="text-stone-500">Delivery address</dt>
                                    <dd className="mt-1 text-stone-900">
                                        {[fulfillment.delivery_recipient_name, fulfillment.delivery_phone]
                                            .filter(Boolean)
                                            .join(' · ')}
                                        <br />
                                        {[fulfillment.delivery_line1, fulfillment.delivery_line2]
                                            .filter(Boolean)
                                            .join(', ')}
                                        <br />
                                        {[fulfillment.delivery_area, fulfillment.delivery_city]
                                            .filter(Boolean)
                                            .join(', ')}
                                    </dd>
                                </div>
                            ) : null}
                        </dl>
                    </div>
                </div>

                <div className="space-y-4">
                    {active ? (
                        <OrderStatusTimeline
                            currentStatus={order.status}
                            history={order.status_history ?? []}
                            fulfillmentType={order.fulfillment_type}
                        />
                    ) : (
                        <FinalOrderStatusCard order={order} />
                    )}

                    {active ? (
                        <Link
                            to={`/orders/${order.order_number}/track`}
                            className="btn-orange btn-shine block w-full px-4 py-2.5 text-center text-sm"
                        >
                            Track order live
                            <span className="btn-arrow" aria-hidden="true">
                                →
                            </span>
                        </Link>
                    ) : null}

                    {active ? <OrderCustomerActions order={order} onChanged={setOrder} /> : null}

                    <div className="space-y-3 rounded-2xl bg-white p-4 ring-1 ring-stone-200">
                        <div className="flex items-center justify-between gap-2">
                            <h2 className="font-semibold text-stone-900">Payment</h2>
                            <PaymentStatusBadge status={payment?.status} />
                        </div>

                        <p className="text-sm text-stone-600">
                            Method:{' '}
                            <span className="font-medium text-stone-900">
                                {payment?.method_label || payment?.method || 'Not selected'}
                            </span>
                        </p>

                        {active ? (
                            <>
                                <PaymentInstructions
                                    method={
                                        payment?.method
                                            ? {
                                                  id: payment.method,
                                                  label: payment.method_label || payment.method,
                                              }
                                            : null
                                    }
                                    details={payment?.details}
                                    instructions={payment?.instructions}
                                    requiresProof={payment?.requires_proof}
                                    qrImage={payment?.qr_image}
                                    amountLabel={
                                        order.total != null
                                            ? `Rs ${Number(order.total).toLocaleString('en-PK')}`
                                            : null
                                    }
                                />

                                {payment?.customer_message ? (
                                    <p className="text-sm text-red-700">{payment.customer_message}</p>
                                ) : null}

                                {payment?.can_change_method ? (
                                    <form onSubmit={handleSelectMethod} className="space-y-2">
                                        <label className="block text-sm font-medium text-stone-700">
                                            Payment method
                                            <select
                                                value={selectedMethod}
                                                onChange={(event) => setSelectedMethod(event.target.value)}
                                                className="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2"
                                                disabled={busy}
                                            >
                                                {methods.map((method) => (
                                                    <option key={method.id} value={method.id}>
                                                        {method.label}
                                                    </option>
                                                ))}
                                            </select>
                                        </label>
                                        <button
                                            type="submit"
                                            disabled={busy}
                                            className="rounded-lg bg-stone-900 px-3 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-50"
                                        >
                                            {busy ? 'Saving…' : 'Save payment method'}
                                        </button>
                                    </form>
                                ) : null}

                                {payment?.can_upload_proof ? (
                                    <PaymentProofUpload
                                        onUpload={handleUpload}
                                        disabled={busy}
                                        error={actionError}
                                    />
                                ) : null}

                                {payment?.proof ? (
                                    <div className="text-sm text-stone-600">
                                        <p>
                                            Proof on file: {payment.proof.original_filename || 'receipt'}
                                        </p>
                                        <button
                                            type="button"
                                            onClick={handleViewProof}
                                            className="mt-1 font-medium text-amber-900 hover:underline"
                                        >
                                            View uploaded proof
                                        </button>
                                    </div>
                                ) : null}

                                {actionError && !payment?.can_upload_proof ? (
                                    <p className="text-sm text-red-700">{actionError}</p>
                                ) : null}
                            </>
                        ) : (
                            <div className="rounded-xl bg-stone-50 px-3 py-3 text-sm text-stone-600 ring-1 ring-stone-200">
                                <p>
                                    This is a historical payment record from when the order was placed. Payment
                                    details cannot be changed.
                                </p>
                                {payment?.proof ? (
                                    <button
                                        type="button"
                                        onClick={handleViewProof}
                                        className="mt-2 font-medium text-amber-900 hover:underline"
                                    >
                                        View uploaded proof
                                    </button>
                                ) : null}
                            </div>
                        )}

                        {payment?.status === 'verified' ? (
                            <p className="text-sm font-medium text-emerald-800">Payment verified.</p>
                        ) : null}

                        {payment?.status === 'submitted' ? (
                            <p className="text-sm text-sky-900">
                                Payment proof submitted. Awaiting verification.
                            </p>
                        ) : null}
                    </div>

                    {active ? (
                        <button
                            type="button"
                            onClick={load}
                            className="w-full rounded-lg px-3 py-2 text-sm font-medium text-stone-700 ring-1 ring-stone-300 hover:bg-stone-100"
                        >
                            Refresh status
                        </button>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
