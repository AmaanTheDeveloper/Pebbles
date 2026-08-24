import { formatMoney } from '../../lib/format';

export default function OrderItems({ items = [] }) {
    return (
        <div className="rounded-2xl bg-white p-4 ring-1 ring-stone-200">
            <h2 className="font-semibold text-stone-900">Items</h2>
            <ul className="mt-3 space-y-3">
                {items.map((item, index) => (
                    <li key={`${item.product_name}-${index}`} className="flex justify-between gap-3 text-sm">
                        <div>
                            <p className="font-medium text-stone-900">
                                {item.quantity}× {item.product_name}
                                {item.variant_name ? ` (${item.variant_name})` : ''}
                            </p>
                            {(item.add_ons ?? []).length > 0 ? (
                                <p className="text-stone-500">
                                    {(item.add_ons ?? []).map((addOn) => addOn.name).join(', ')}
                                </p>
                            ) : null}
                        </div>
                        <p className="font-medium text-stone-800">{formatMoney(item.line_total)}</p>
                    </li>
                ))}
            </ul>
        </div>
    );
}
