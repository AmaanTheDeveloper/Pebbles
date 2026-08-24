import { formatMoney } from '../../lib/format';

export default function OrderSummary({ order }) {
    return (
        <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200">
            <h2 className="font-semibold text-stone-900">Summary</h2>
            <dl className="mt-3 space-y-2 text-sm">
                <div className="flex justify-between text-stone-600">
                    <dt>Subtotal</dt>
                    <dd>{formatMoney(order.subtotal)}</dd>
                </div>
                <div className="flex justify-between text-stone-600">
                    <dt>Delivery fee</dt>
                    <dd>{formatMoney(order.delivery_fee)}</dd>
                </div>
                <div className="flex justify-between border-t border-stone-200 pt-2 text-base font-semibold text-stone-900">
                    <dt>Total</dt>
                    <dd>{formatMoney(order.total)}</dd>
                </div>
            </dl>
        </div>
    );
}
