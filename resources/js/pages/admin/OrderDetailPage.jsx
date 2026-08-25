import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
    fetchAdminOrder,
    fetchPaymentProof,
    rejectOrderPayment,
    updateAdminOrderStatus,
    verifyOrderPayment,
} from '../../api/admin/orders';
import FlashMessage from '../../components/admin/FlashMessage';
import { AdminSelectField, AdminTextareaField } from '../../components/admin/AdminFormField';
import StatusBadge from '../../components/admin/StatusBadge';
import DownloadOrderReceiptButton from '../../components/orders/DownloadOrderReceiptButton';
import ErrorState from '../../components/storefront/ErrorState';
import LoadingState from '../../components/storefront/LoadingState';
import { AdminPageHeader, AdminPanel, AdminPanelBody } from '../../layouts/AdminLayout';
import { formatDate, formatMoney, humanizeStatus } from '../../lib/format';
import { normalizeApiError } from '../../lib/errors';
import { useVisibilityPolling } from '../../hooks/useVisibilityPolling';

const TERMINAL_STATUSES = new Set(['completed', 'cancelled']);

export default function AdminOrderDetailPage() {
    const { orderNumber } = useParams();
    const [order, setOrder] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [nextStatus, setNextStatus] = useState('');
    const [statusNote, setStatusNote] = useState('');
    const [paymentNote, setPaymentNote] = useState('');
    const [rejectReason, setRejectReason] = useState('');
    const [saving, setSaving] = useState(false);
    const [flash, setFlash] = useState(null);
    const [proofUrl, setProofUrl] = useState(null);

    const load = useCallback(async ({ silent = false } = {}) => {
        if (!silent) {
            setLoading(true);
        }

        setError(null);

        try {
            const data = await fetchAdminOrder(orderNumber);
            setOrder(data);
            setNextStatus(data.allowed_next_statuses?.[0] ?? '');
        } catch (err) {
            setError(normalizeApiError(err));

            if (!silent) {
                setOrder(null);
            }
        } finally {
            if (!silent) {
                setLoading(false);
            }
        }
    }, [orderNumber]);

    useEffect(() => {
        load();
    }, [load]);

    useVisibilityPolling(
        () => {
            load({ silent: true });
        },
        {
            enabled: Boolean(order?.status && !TERMINAL_STATUSES.has(order.status)),
            visibleMs: 10000,
            hiddenMs: 30000,
        },
    );

    useEffect(() => {
        let objectUrl;

        const loadProof = async () => {
            if (!order?.payment?.proof?.id) {
                setProofUrl(null);
                return;
            }

            try {
                const blob = await fetchPaymentProof(orderNumber, order.payment.proof.id);
                objectUrl = URL.createObjectURL(blob);
                setProofUrl(objectUrl);
            } catch {
                setProofUrl(null);
            }
        };

        loadProof();

        return () => {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
        };
    }, [order?.payment?.proof?.id, orderNumber]);

    const handleStatusUpdate = async (event) => {
        event.preventDefault();
        if (!nextStatus) {
            return;
        }

        setSaving(true);
        setFlash(null);

        try {
            const updated = await updateAdminOrderStatus(orderNumber, {
                status: nextStatus,
                note: statusNote || undefined,
            });
            setOrder(updated);
            setNextStatus(updated.allowed_next_statuses?.[0] ?? '');
            setStatusNote('');
            setFlash({ type: 'success', message: 'Order status updated.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    const handleVerifyPayment = async () => {
        setSaving(true);
        setFlash(null);

        try {
            const updated = await verifyOrderPayment(orderNumber, { note: paymentNote || undefined });
            setOrder(updated);
            setPaymentNote('');
            setFlash({ type: 'success', message: 'Payment verified.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    const handleRejectPayment = async () => {
        setSaving(true);
        setFlash(null);

        try {
            const updated = await rejectOrderPayment(orderNumber, { reason: rejectReason || undefined });
            setOrder(updated);
            setRejectReason('');
            setFlash({ type: 'success', message: 'Payment rejected.' });
        } catch (err) {
            setFlash({ type: 'error', message: normalizeApiError(err).message });
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <LoadingState label="Loading order…" />;
    }

    if (error) {
        return <ErrorState message={error.message} onRetry={load} />;
    }

    if (!order) {
        return null;
    }

    const canVerifyPayment = order.payment?.status === 'submitted';
    const proof = order.payment?.proof;

    return (
        <div className="space-y-6">
            <AdminPageHeader
                title={`Order ${order.order_number}`}
                description={`Placed ${formatDate(order.created_at)} · ${humanizeStatus(order.fulfillment_type)}`}
                actions={
                    <div className="flex flex-wrap items-center gap-3">
                        <DownloadOrderReceiptButton
                            orderNumber={order.order_number}
                            variant="admin"
                            label="Download Receipt"
                        />
                        <Link to="/admin/orders" className="text-sm font-medium text-amber-900 hover:underline">
                            Back to orders
                        </Link>
                    </div>
                }
            />

            {flash ? <FlashMessage type={flash.type} message={flash.message} onDismiss={() => setFlash(null)} /> : null}

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <AdminPanel>
                        <AdminPanelBody>
                            <div className="flex flex-wrap items-center gap-3">
                                <StatusBadge status={order.status} />
                                {order.payment?.status ? (
                                    <StatusBadge status={order.payment.status} kind="payment" />
                                ) : null}
                            </div>

                            <h2 className="mt-6 text-sm font-semibold text-stone-900">Items</h2>
                            <ul className="mt-3 divide-y divide-stone-100">
                                {order.items?.map((item, index) => (
                                    <li key={`${item.product_name}-${index}`} className="py-3">
                                        <div className="flex justify-between gap-4">
                                            <div>
                                                <p className="font-medium text-stone-900">
                                                    {item.quantity}× {item.product_name}
                                                    {item.variant_name ? ` (${item.variant_name})` : ''}
                                                </p>
                                                {item.add_ons?.length ? (
                                                    <ul className="mt-1 text-xs text-stone-500">
                                                        {item.add_ons.map((addOn, addOnIndex) => (
                                                            <li key={addOnIndex}>
                                                                + {addOn.quantity}× {addOn.name} ({formatMoney(addOn.line_total)})
                                                            </li>
                                                        ))}
                                                    </ul>
                                                ) : null}
                                            </div>
                                            <p className="font-medium">{formatMoney(item.line_total)}</p>
                                        </div>
                                    </li>
                                ))}
                            </ul>

                            <dl className="mt-4 space-y-1 border-t border-stone-100 pt-4 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-stone-600">Subtotal</dt>
                                    <dd>{formatMoney(order.subtotal)}</dd>
                                </div>
                                {Number(order.discount_amount ?? 0) - Number(order.coupon_discount_amount ?? 0) > 0 ? (
                                    <div className="flex justify-between">
                                        <dt className="text-stone-600">Discount</dt>
                                        <dd>
                                            -
                                            {formatMoney(
                                                Number(order.discount_amount) - Number(order.coupon_discount_amount ?? 0),
                                            )}
                                        </dd>
                                    </div>
                                ) : null}
                                {Number(order.coupon_discount_amount ?? 0) > 0 ? (
                                    <div className="flex justify-between">
                                        <dt className="text-stone-600">
                                            {order.coupon_code ? `Coupon (${order.coupon_code})` : 'Coupon'}
                                        </dt>
                                        <dd>-{formatMoney(order.coupon_discount_amount)}</dd>
                                    </div>
                                ) : null}
                                <div className="flex justify-between">
                                    <dt className="text-stone-600">Delivery fee</dt>
                                    <dd>{formatMoney(order.delivery_fee)}</dd>
                                </div>
                                <div className="flex justify-between font-semibold">
                                    <dt>Total</dt>
                                    <dd>{formatMoney(order.total)}</dd>
                                </div>
                            </dl>
                        </AdminPanelBody>
                    </AdminPanel>

                    <AdminPanel>
                        <AdminPanelBody>
                            <h2 className="text-sm font-semibold text-stone-900">Status history</h2>
                            <ul className="mt-3 space-y-3">
                                {order.status_history?.map((entry, index) => (
                                    <li key={index} className="rounded-lg bg-stone-50 px-3 py-2 text-sm">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <StatusBadge status={entry.status} />
                                            <span className="text-stone-500">{formatDate(entry.created_at)}</span>
                                        </div>
                                        {entry.note ? <p className="mt-1 text-stone-600">{entry.note}</p> : null}
                                        {entry.changed_by ? (
                                            <p className="mt-1 text-xs text-stone-500">By {entry.changed_by}</p>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        </AdminPanelBody>
                    </AdminPanel>
                </div>

                <div className="space-y-6">
                    <AdminPanel>
                        <AdminPanelBody className="space-y-4">
                            <h2 className="text-sm font-semibold text-stone-900">Customer</h2>
                            <p className="text-sm">
                                <span className="font-medium">{order.customer?.name}</span>
                                <br />
                                <span className="text-stone-600">{order.customer?.email}</span>
                            </p>
                            <p className="text-sm text-stone-600">Branch: {order.branch?.name ?? '—'}</p>
                            {order.customer_notes ? (
                                <p className="text-sm text-stone-600">Notes: {order.customer_notes}</p>
                            ) : null}
                        </AdminPanelBody>
                    </AdminPanel>

                    {order.fulfillment_type === 'delivery' ? (
                        <AdminPanel>
                            <AdminPanelBody className="space-y-2 text-sm">
                                <h2 className="font-semibold text-stone-900">Delivery</h2>
                                <p>{order.fulfillment?.delivery_recipient_name}</p>
                                <p>{order.fulfillment?.delivery_phone}</p>
                                <p>{order.fulfillment?.delivery_line1}</p>
                                {order.fulfillment?.delivery_line2 ? <p>{order.fulfillment.delivery_line2}</p> : null}
                                <p>
                                    {[order.fulfillment?.delivery_area, order.fulfillment?.delivery_city]
                                        .filter(Boolean)
                                        .join(', ')}
                                </p>
                            </AdminPanelBody>
                        </AdminPanel>
                    ) : null}

                    {order.allowed_next_statuses?.length ? (
                        <AdminPanel>
                            <AdminPanelBody>
                                <form onSubmit={handleStatusUpdate} className="space-y-4">
                                    <h2 className="text-sm font-semibold text-stone-900">Update status</h2>
                                    <AdminSelectField
                                        label="Next status"
                                        name="next_status"
                                        value={nextStatus}
                                        onChange={(event) => setNextStatus(event.target.value)}
                                        options={order.allowed_next_statuses.map((status) => ({
                                            value: status,
                                            label: humanizeStatus(status),
                                        }))}
                                    />
                                    <AdminTextareaField
                                        label="Note (optional)"
                                        name="status_note"
                                        value={statusNote}
                                        onChange={(event) => setStatusNote(event.target.value)}
                                        rows={3}
                                    />
                                    <button
                                        type="submit"
                                        disabled={saving || !nextStatus}
                                        className="w-full rounded-md bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                                    >
                                        {saving ? 'Saving…' : 'Update status'}
                                    </button>
                                </form>
                            </AdminPanelBody>
                        </AdminPanel>
                    ) : null}

                    {order.payment ? (
                        <AdminPanel>
                            <AdminPanelBody className="space-y-4">
                                <h2 className="text-sm font-semibold text-stone-900">Payment</h2>
                                <p className="text-sm text-stone-600">
                                    {humanizeStatus(order.payment.method)} · {formatMoney(order.payment.amount)}
                                </p>
                                {order.payment.transaction_reference ? (
                                    <p className="text-sm">Ref: {order.payment.transaction_reference}</p>
                                ) : null}
                                {proof ? (
                                    <div className="space-y-2">
                                        <p className="text-sm font-medium">{proof.original_filename}</p>
                                        {proofUrl && proof.mime_type?.startsWith('image/') ? (
                                            <img src={proofUrl} alt="Payment proof" className="max-h-48 rounded-md border" />
                                        ) : null}
                                        {proofUrl ? (
                                            <a
                                                href={proofUrl}
                                                download={proof.original_filename || 'payment-proof'}
                                                className="text-sm font-medium text-amber-900 hover:underline"
                                            >
                                                Download proof
                                            </a>
                                        ) : null}
                                    </div>
                                ) : null}
                                {canVerifyPayment ? (
                                    <>
                                        <AdminTextareaField
                                            label="Verification note"
                                            name="payment_note"
                                            value={paymentNote}
                                            onChange={(event) => setPaymentNote(event.target.value)}
                                            rows={2}
                                        />
                                        <button
                                            type="button"
                                            onClick={handleVerifyPayment}
                                            disabled={saving}
                                            className="w-full rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-600 disabled:opacity-60"
                                        >
                                            Verify payment
                                        </button>
                                        <AdminTextareaField
                                            label="Rejection reason"
                                            name="reject_reason"
                                            value={rejectReason}
                                            onChange={(event) => setRejectReason(event.target.value)}
                                            rows={2}
                                        />
                                        <button
                                            type="button"
                                            onClick={handleRejectPayment}
                                            disabled={saving}
                                            className="w-full rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-50 disabled:opacity-60"
                                        >
                                            Reject payment
                                        </button>
                                    </>
                                ) : null}
                            </AdminPanelBody>
                        </AdminPanel>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
