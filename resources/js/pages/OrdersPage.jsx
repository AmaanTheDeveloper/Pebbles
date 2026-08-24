import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchOrders } from '../api/orders';
import EmptyState from '../components/storefront/EmptyState';
import ErrorState from '../components/storefront/ErrorState';
import LoadingState from '../components/storefront/LoadingState';
import OrderCard from '../components/orders/OrderCard';
import { normalizeApiError } from '../lib/errors';

export default function OrdersPage() {
    const [activeOrders, setActiveOrders] = useState([]);
    const [recentOrders, setRecentOrders] = useState([]);
    const [recentMeta, setRecentMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [loadingMore, setLoadingMore] = useState(false);
    const [error, setError] = useState(null);

    const load = async ({ recentPage = 1, appendRecent = false } = {}) => {
        if (recentPage === 1 && !appendRecent) {
            setLoading(true);
        } else {
            setLoadingMore(true);
        }

        setError(null);

        try {
            const payload = await fetchOrders({ recentPage });
            const active = payload.data?.active ?? [];
            const recent = payload.data?.recent ?? [];

            setActiveOrders(active);
            setRecentOrders((current) => (appendRecent ? [...current, ...recent] : recent));
            setRecentMeta(payload.meta?.recent ?? null);
        } catch (err) {
            setError(normalizeApiError(err));
            if (!appendRecent) {
                setActiveOrders([]);
                setRecentOrders([]);
            }
        } finally {
            setLoading(false);
            setLoadingMore(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    if (loading) {
        return <LoadingState label="Loading orders…" />;
    }

    if (error) {
        return <ErrorState message={error.message} onRetry={() => load()} />;
    }

    const hasActive = activeOrders.length > 0;
    const hasRecent = recentOrders.length > 0;

    if (!hasActive && !hasRecent) {
        return (
            <div className="space-y-4">
                <EmptyState
                    title="No orders yet"
                    description="When you place an order, it will show up here."
                />
                <Link to="/menu" className="inline-flex text-sm font-medium text-amber-900 hover:underline">
                    Browse menu
                </Link>
            </div>
        );
    }

    return (
        <div className="space-y-8">
            <div>
                <h1 className="text-2xl font-semibold text-stone-900">My orders</h1>
                <p className="text-sm text-stone-600">Track active orders and review your order history.</p>
            </div>

            <section className="space-y-3">
                <div>
                    <h2 className="text-lg font-semibold text-stone-900">Active orders</h2>
                    <p className="text-sm text-stone-500">Orders currently in progress.</p>
                </div>
                {hasActive ? (
                    <div className="space-y-3">
                        {activeOrders.map((order) => (
                            <OrderCard key={order.order_number} order={order} variant="active" />
                        ))}
                    </div>
                ) : (
                    <div className="rounded-2xl bg-stone-50 px-4 py-5 text-sm text-stone-600 ring-1 ring-stone-200">
                        You don&apos;t have any active orders right now.
                    </div>
                )}
            </section>

            <section className="space-y-3">
                <div>
                    <h2 className="text-lg font-semibold text-stone-900">Recent orders</h2>
                    <p className="text-sm text-stone-500">Completed and cancelled orders.</p>
                </div>
                {hasRecent ? (
                    <div className="space-y-3">
                        {recentOrders.map((order) => (
                            <OrderCard key={order.order_number} order={order} variant="recent" />
                        ))}
                        {recentMeta && recentMeta.current_page < recentMeta.last_page ? (
                            <button
                                type="button"
                                onClick={() =>
                                    load({
                                        recentPage: recentMeta.current_page + 1,
                                        appendRecent: true,
                                    })
                                }
                                disabled={loadingMore}
                                className="w-full rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 ring-1 ring-stone-300 hover:bg-stone-50 disabled:opacity-60"
                            >
                                {loadingMore ? 'Loading…' : 'Load more recent orders'}
                            </button>
                        ) : null}
                    </div>
                ) : (
                    <div className="rounded-2xl bg-stone-50 px-4 py-5 text-sm text-stone-600 ring-1 ring-stone-200">
                        No recent orders yet.
                    </div>
                )}
            </section>
        </div>
    );
}
