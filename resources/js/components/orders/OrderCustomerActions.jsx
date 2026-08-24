import { useState } from 'react';
import { Link } from 'react-router-dom';
import { cancelOrder } from '../../api/orders';
import { useActiveOrders } from '../../contexts/ActiveOrdersContext';
import { normalizeApiError } from '../../lib/errors';
import CancelOrderModal from './CancelOrderModal';

export default function OrderCustomerActions({ order, onChanged }) {
    const actions = order?.customer_actions ?? {};
    const { refreshActiveOrders } = useActiveOrders();
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [cancelOpen, setCancelOpen] = useState(false);

    const canCancel = Boolean(actions.can_cancel);
    const canModify = Boolean(actions.can_modify);
    const changesClosed = Boolean(actions.changes_closed);

    if (order?.status === 'cancelled') {
        return (
            <div className="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
                Order cancelled
            </div>
        );
    }

    if (order?.status === 'completed') {
        return null;
    }

    const handleCancel = async (payload) => {
        setBusy(true);
        setError(null);
        setSuccess(null);

        try {
            const updated = await cancelOrder(order.order_number, payload);
            setCancelOpen(false);
            setSuccess('Order cancelled successfully.');
            await refreshActiveOrders();
            onChanged?.(updated);
        } catch (err) {
            setError(normalizeApiError(err).message);
        } finally {
            setBusy(false);
        }
    };

    if (changesClosed) {
        return (
            <div className="space-y-2 rounded-2xl bg-stone-50 p-4 ring-1 ring-stone-200 transition-opacity duration-300">
                <h2 className="font-semibold text-stone-900">
                    {actions.changes_closed_heading ?? 'Cancel or Modify Order window is closed.'}
                </h2>
                <p className="text-sm leading-relaxed text-stone-600">
                    {actions.changes_closed_message ??
                        'Your order has been confirmed and is now being prepared. Changes or cancellation are no longer available.'}
                </p>
            </div>
        );
    }

    if (!canCancel && !canModify) {
        const unavailableReason =
            actions.changes_unavailable_reason ??
            actions.cancel_unavailable_reason ??
            actions.modify_unavailable_reason;

        if (!unavailableReason) {
            return null;
        }

        return (
            <div className="rounded-xl bg-stone-50 px-4 py-3 text-sm text-stone-600 ring-1 ring-stone-200">
                {unavailableReason}
            </div>
        );
    }

    return (
        <div className="space-y-3 rounded-2xl bg-white p-4 ring-1 ring-stone-200">
            <div>
                <h2 className="font-semibold text-stone-900">Order changes</h2>
                {order?.status === 'pending' ? (
                    <p className="mt-1 text-sm text-stone-600">
                        You can cancel or modify this order while it is awaiting confirmation.
                    </p>
                ) : null}
            </div>

            {success ? <p className="text-sm font-medium text-emerald-800">{success}</p> : null}
            {error ? <p className="text-sm text-red-700">{error}</p> : null}

            <div className="flex flex-wrap gap-2">
                {canModify ? (
                    <Link
                        to={`/order/${order.order_number}/modify`}
                        className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800"
                    >
                        Modify order
                    </Link>
                ) : null}
                {canCancel ? (
                    <button
                        type="button"
                        onClick={() => setCancelOpen(true)}
                        disabled={busy}
                        className="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-50 disabled:opacity-60"
                    >
                        Cancel order
                    </button>
                ) : null}
            </div>

            <CancelOrderModal
                open={cancelOpen}
                orderNumber={order.order_number}
                busy={busy}
                error={error}
                onClose={() => setCancelOpen(false)}
                onConfirm={handleCancel}
            />
        </div>
    );
}
