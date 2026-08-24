import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { fetchOrder, modifyOrder, previewModifyOrder } from '../api/orders';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import { formatMoney } from '../lib/format';
import { normalizeApiError } from '../lib/errors';

function buildPayload(items, order) {
    return {
        items: items.map((item) => ({
            product_id: item.product_id,
            product_variant_id: item.product_variant_id,
            quantity: item.quantity,
            add_on_ids: (item.add_ons ?? []).map((addOn) => addOn.add_on_id).filter(Boolean),
        })),
        customer_notes: order.customer_notes ?? null,
        pickup_notes: order.fulfillment?.pickup_notes ?? null,
        delivery_instructions: order.fulfillment?.delivery_instructions ?? null,
    };
}

export default function ModifyOrderPage() {
    const { orderNumber } = useParams();
    const navigate = useNavigate();
    const [order, setOrder] = useState(null);
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [preview, setPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [step, setStep] = useState('edit');
    const [actionError, setActionError] = useState(null);

    useEffect(() => {
        let cancelled = false;

        (async () => {
            setLoading(true);
            setError(null);

            try {
                const nextOrder = await fetchOrder(orderNumber);
                if (cancelled) {
                    return;
                }

                if (!nextOrder?.customer_actions?.can_modify) {
                    setError({
                        message:
                            nextOrder?.customer_actions?.modify_unavailable_reason ??
                            'This order can no longer be modified.',
                    });
                    setOrder(nextOrder);
                    return;
                }

                setOrder(nextOrder);
                setItems(
                    (nextOrder.items ?? []).map((item) => ({
                        ...item,
                        quantity: item.quantity ?? 1,
                    })),
                );
            } catch (err) {
                if (!cancelled) {
                    setError(normalizeApiError(err));
                }
            } finally {
                if (!cancelled) {
                    setLoading(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [orderNumber]);

    const payload = useMemo(() => (order ? buildPayload(items, order) : null), [items, order]);

    const updateQuantity = (index, delta) => {
        setItems((current) =>
            current.map((item, itemIndex) =>
                itemIndex === index
                    ? { ...item, quantity: Math.max(1, Math.min(99, item.quantity + delta)) }
                    : item,
            ),
        );
        setPreview(null);
    };

    const removeItem = (index) => {
        setItems((current) => current.filter((_, itemIndex) => itemIndex !== index));
        setPreview(null);
    };

    const handleReview = async () => {
        if (!payload || items.length === 0) {
            setActionError('At least one item is required.');
            return;
        }

        setPreviewLoading(true);
        setActionError(null);

        try {
            const nextPreview = await previewModifyOrder(orderNumber, payload);
            setPreview(nextPreview);
            setStep('review');
        } catch (err) {
            setActionError(normalizeApiError(err).message);
        } finally {
            setPreviewLoading(false);
        }
    };

    const handleConfirm = async () => {
        if (!payload || submitting) {
            return;
        }

        setSubmitting(true);
        setActionError(null);

        try {
            const response = await modifyOrder(orderNumber, payload);
            navigate(`/order/${orderNumber}`, {
                replace: true,
                state: { flash: response.message ?? 'Your order has been updated successfully.' },
            });
        } catch (err) {
            setActionError(normalizeApiError(err).message);
        } finally {
            setSubmitting(false);
        }
    };

    if (loading) {
        return <LoadingState label="Loading order…" />;
    }

    if (error && !order) {
        return <ErrorState message={error.message} />;
    }

    if (order && !order.customer_actions?.can_modify) {
        return (
            <div className="space-y-4">
                <ErrorState message={error?.message ?? 'This order can no longer be modified.'} />
                <Link
                    to={`/order/${orderNumber}`}
                    className="inline-flex text-sm font-medium text-amber-900 hover:underline"
                >
                    Back to order
                </Link>
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-3xl space-y-6">
            <div>
                <Link to={`/order/${orderNumber}`} className="text-sm font-medium text-amber-900 hover:underline">
                    ← Back to order
                </Link>
                <h1 className="mt-2 text-2xl font-semibold text-stone-900">Modify order</h1>
                <p className="text-sm text-stone-600">{orderNumber}</p>
            </div>

            {step === 'edit' ? (
                <div className="space-y-4 rounded-2xl bg-white p-5 ring-1 ring-stone-200">
                    <h2 className="font-semibold text-stone-900">Edit items</h2>
                    <ul className="space-y-3">
                        {items.map((item, index) => (
                            <li
                                key={`${item.product_id}-${item.product_variant_id}-${index}`}
                                className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-stone-200 px-3 py-3"
                            >
                                <div>
                                    <p className="font-medium text-stone-900">
                                        {item.product_name}
                                        {item.variant_name ? ` (${item.variant_name})` : ''}
                                    </p>
                                    {(item.add_ons ?? []).length > 0 ? (
                                        <p className="text-xs text-stone-500">
                                            {(item.add_ons ?? []).map((addOn) => addOn.name).join(', ')}
                                        </p>
                                    ) : null}
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => updateQuantity(index, -1)}
                                        className="rounded-md bg-stone-100 px-2 py-1 text-sm"
                                    >
                                        −
                                    </button>
                                    <span className="min-w-8 text-center text-sm font-medium">{item.quantity}</span>
                                    <button
                                        type="button"
                                        onClick={() => updateQuantity(index, 1)}
                                        className="rounded-md bg-stone-100 px-2 py-1 text-sm"
                                    >
                                        +
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => removeItem(index)}
                                        className="rounded-md px-2 py-1 text-sm text-red-700 hover:bg-red-50"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </li>
                        ))}
                    </ul>

                    {actionError ? <p className="text-sm text-red-700">{actionError}</p> : null}

                    <button
                        type="button"
                        onClick={handleReview}
                        disabled={previewLoading || items.length === 0}
                        className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                    >
                        {previewLoading ? 'Calculating…' : 'Review changes'}
                    </button>
                </div>
            ) : (
                <div className="space-y-4 rounded-2xl bg-white p-5 ring-1 ring-stone-200">
                    <h2 className="font-semibold text-stone-900">Review order changes</h2>
                    <ul className="space-y-2 text-sm text-stone-700">
                        {items.map((item, index) => (
                            <li key={`review-${index}`}>
                                {item.quantity}× {item.product_name}
                                {item.variant_name ? ` (${item.variant_name})` : ''}
                            </li>
                        ))}
                    </ul>
                    <div className="space-y-1 border-t border-stone-200 pt-3 text-sm">
                        <div className="flex justify-between">
                            <span>Previous total</span>
                            <span>{formatMoney(preview?.previous_total ?? order.total)}</span>
                        </div>
                        <div className="flex justify-between font-semibold text-stone-900">
                            <span>New total</span>
                            <span>{formatMoney(preview?.new_total ?? order.total)}</span>
                        </div>
                    </div>

                    {actionError ? <p className="text-sm text-red-700">{actionError}</p> : null}

                    <div className="flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() => setStep('edit')}
                            disabled={submitting}
                            className="rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 ring-1 ring-stone-300 hover:bg-stone-50"
                        >
                            Back
                        </button>
                        <button
                            type="button"
                            onClick={handleConfirm}
                            disabled={submitting}
                            className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-60"
                        >
                            {submitting ? 'Saving…' : 'Confirm changes'}
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
