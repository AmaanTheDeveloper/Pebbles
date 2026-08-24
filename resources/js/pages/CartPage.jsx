import { Link, useLocation } from 'react-router-dom';
import EmptyState from '../components/storefront/EmptyState';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import ReorderSummaryNotice from '../components/orders/ReorderSummaryNotice';
import { useCart } from '../contexts/CartContext';

function formatMoney(value) {
    return `Rs ${Number(value ?? 0).toLocaleString('en-PK', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}`;
}

export default function CartPage() {
    const location = useLocation();
    const { cart, loading, mutating, error, updateItem, removeItem, clear, refreshCart } = useCart();
    const reorderFlash = location.state?.flash ?? null;
    const reorderSummary = location.state?.reorderSummary ?? null;

    if (loading) {
        return <LoadingState label="Loading cart…" />;
    }

    if (error && (!cart?.items || cart.items.length === 0)) {
        return <ErrorState message={error.message} onRetry={refreshCart} />;
    }

    if (!cart?.items?.length) {
        return (
            <div className="space-y-4">
                <EmptyState
                    title="Your cart is empty"
                    description="Browse the menu for shakes, ice cream, Limca, and more."
                />
                <Link to="/menu" className="inline-flex text-sm font-medium text-amber-900 hover:underline">
                    Browse menu
                </Link>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <ReorderSummaryNotice summary={reorderSummary} flash={reorderFlash} />

            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold text-stone-900">Your cart</h1>
                    <p className="text-sm text-stone-600">{cart.item_count} item(s)</p>
                </div>
                <div className="flex gap-2">
                    <button
                        type="button"
                        disabled={mutating}
                        onClick={() => clear()}
                        className="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 hover:bg-stone-200/70 disabled:opacity-50"
                    >
                        Clear cart
                    </button>
                    <Link
                        to="/checkout"
                        className="rounded-lg bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-800"
                    >
                        Checkout
                    </Link>
                </div>
            </div>

            {error ? <ErrorState message={error.message} onRetry={refreshCart} /> : null}

            <div className="space-y-4">
                {cart.items.map((item) => (
                    <div
                        key={item.id}
                        className="rounded-2xl bg-white p-4 ring-1 ring-stone-200"
                    >
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div className="space-y-1">
                                <h2 className="font-semibold text-stone-900">{item.product?.name}</h2>
                                {item.variant ? (
                                    <p className="text-sm text-stone-600">{item.variant.name}</p>
                                ) : null}
                                {(item.add_ons ?? []).length > 0 ? (
                                    <p className="text-sm text-stone-500">
                                        {(item.add_ons ?? []).map((addOn) => addOn.name).join(', ')}
                                    </p>
                                ) : null}
                                {!item.available ? (
                                    <p className="text-sm text-red-700">{item.error || 'Unavailable'}</p>
                                ) : null}
                            </div>
                            <div className="text-right">
                                <p className="font-semibold text-amber-950">
                                    {item.line_total !== null ? formatMoney(item.line_total) : '—'}
                                </p>
                                <p className="text-xs text-stone-500">
                                    {item.unit_price !== null ? `${formatMoney(item.unit_price)} each` : ''}
                                </p>
                            </div>
                        </div>
                        <div className="mt-4 flex items-center gap-3">
                            <label className="flex items-center gap-2 text-sm text-stone-600">
                                Qty
                                <input
                                    type="number"
                                    min={1}
                                    max={99}
                                    disabled={mutating}
                                    value={item.quantity}
                                    onChange={(event) => {
                                        const quantity = Number(event.target.value);
                                        if (quantity >= 1 && quantity <= 99) {
                                            updateItem(item.id, { quantity });
                                        }
                                    }}
                                    className="w-20 rounded-lg border border-stone-300 px-2 py-1"
                                />
                            </label>
                            <button
                                type="button"
                                disabled={mutating}
                                onClick={() => removeItem(item.id)}
                                className="text-sm font-medium text-red-700 hover:underline disabled:opacity-50"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                ))}
            </div>

            <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200">
                <div className="flex justify-between text-sm text-stone-600">
                    <span>Subtotal</span>
                    <span>{formatMoney(cart.subtotal)}</span>
                </div>
                {Number(cart.discount_amount ?? 0) > 0 ? (
                    <div className="mt-2 flex justify-between text-sm text-stone-600">
                        <span>{cart.discount_label ? `Discount (${cart.discount_label})` : 'Discount'}</span>
                        <span>-{formatMoney(cart.discount_amount)}</span>
                    </div>
                ) : null}
                <div className="mt-2 flex justify-between text-base font-semibold text-stone-900">
                    <span>Total</span>
                    <span>{formatMoney(cart.total)}</span>
                </div>
                <p className="mt-2 text-xs text-stone-500">
                    Delivery fees are calculated at checkout for delivery orders.
                </p>
                <Link
                    to="/menu"
                    className="mt-4 inline-block text-sm font-medium text-amber-900 hover:underline"
                >
                    Continue shopping
                </Link>
            </div>
        </div>
    );
}
