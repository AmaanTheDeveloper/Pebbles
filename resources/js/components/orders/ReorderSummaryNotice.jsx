export default function ReorderSummaryNotice({ summary, flash }) {
    if (!flash && !summary) {
        return null;
    }

    const hasUnavailable = (summary?.unavailable?.length ?? 0) > 0;

    return (
        <div
            className={[
                'rounded-2xl px-4 py-4 ring-1',
                hasUnavailable ? 'bg-amber-50 ring-amber-200' : 'bg-emerald-50 ring-emerald-200',
            ].join(' ')}
        >
            {flash ? (
                <p className={['text-sm font-medium', hasUnavailable ? 'text-amber-900' : 'text-emerald-900'].join(' ')}>
                    {flash}
                </p>
            ) : null}

            {summary?.added?.length ? (
                <div className="mt-3">
                    <p className="text-sm font-semibold text-stone-900">Added</p>
                    <ul className="mt-1 space-y-1 text-sm text-stone-700">
                        {summary.added.map((item, index) => (
                            <li key={`added-${index}`}>
                                ✓ {item.quantity}× {item.product_name}
                                {item.variant_name ? ` (${item.variant_name})` : ''}
                                {item.note ? (
                                    <span className="block text-xs text-stone-500">{item.note}</span>
                                ) : null}
                            </li>
                        ))}
                    </ul>
                </div>
            ) : null}

            {summary?.unavailable?.length ? (
                <div className="mt-3">
                    <p className="text-sm font-semibold text-stone-900">Unavailable</p>
                    <ul className="mt-1 space-y-1 text-sm text-stone-700">
                        {summary.unavailable.map((item, index) => (
                            <li key={`unavailable-${index}`}>
                                ✕ {item.quantity}× {item.product_name}
                                {item.variant_name ? ` (${item.variant_name})` : ''}
                                {item.reason ? (
                                    <span className="block text-xs text-stone-500">{item.reason}</span>
                                ) : null}
                            </li>
                        ))}
                    </ul>
                </div>
            ) : null}
        </div>
    );
}
