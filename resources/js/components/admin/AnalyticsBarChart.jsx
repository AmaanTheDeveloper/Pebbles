function safeNumber(value) {
    const parsed = Number(value ?? 0);

    return Number.isFinite(parsed) ? parsed : 0;
}

export default function AnalyticsBarChart({
    items = [],
    emptyMessage = 'No data for this period.',
    orientation = 'horizontal',
    valueFormatter = (value) => String(value),
    barClassName = 'fill-amber-700',
    maxBars,
}) {
    const normalized = items
        .map((item) => ({
            label: item.label ?? '—',
            value: safeNumber(item.value),
        }))
        .filter((item) => item.label !== '—' || item.value > 0);

    const displayItems = maxBars ? normalized.slice(0, maxBars) : normalized;
    const hasData = displayItems.some((item) => item.value > 0);
    const maxValue = Math.max(...displayItems.map((item) => item.value), 1);

    if (!displayItems.length || !hasData) {
        return <p className="text-sm text-stone-500">{emptyMessage}</p>;
    }

    if (orientation === 'vertical') {
        const width = 100;
        const height = 48;
        const barWidth = width / displayItems.length;

        return (
            <div className="admin-chart-panel">
                <div className="overflow-x-auto">
                <svg
                    viewBox={`0 0 ${width} ${height + 8}`}
                    className="h-40 w-full min-w-[280px]"
                    role="img"
                    aria-label="Bar chart"
                >
                    {displayItems.map((item, index) => {
                        const barHeight = (item.value / maxValue) * height;
                        const x = index * barWidth + barWidth * 0.15;
                        const w = barWidth * 0.7;

                        return (
                            <g key={`${item.label}-${index}`}>
                                <rect
                                    x={x}
                                    y={height - barHeight}
                                    width={w}
                                    height={barHeight || 0}
                                    className={barClassName}
                                    rx="0.5"
                                >
                                    <title>{`${item.label}: ${valueFormatter(item.value)}`}</title>
                                </rect>
                            </g>
                        );
                    })}
                </svg>
                <div
                    className="mt-2 grid gap-1 text-[10px] text-stone-500"
                    style={{ gridTemplateColumns: `repeat(${displayItems.length}, minmax(0, 1fr))` }}
                >
                    {displayItems.map((item, index) => (
                        <span key={`${item.label}-label-${index}`} className="truncate text-center" title={item.label}>
                            {item.label}
                        </span>
                    ))}
                </div>
                </div>
            </div>
        );
    }

    return (
        <ul className="space-y-2" role="list">
            {displayItems.map((item, index) => {
                const widthPercent = (item.value / maxValue) * 100;

                return (
                    <li key={`${item.label}-${index}`}>
                        <div className="mb-1 flex items-center justify-between gap-2 text-xs">
                            <span className="truncate font-medium text-stone-700">{item.label}</span>
                            <span className="shrink-0 text-stone-500">{valueFormatter(item.value)}</span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-full bg-stone-100">
                            <div
                                className="h-full rounded-full bg-amber-700 transition-all"
                                style={{ width: `${widthPercent}%` }}
                                role="meter"
                                aria-valuenow={item.value}
                                aria-valuemin={0}
                                aria-valuemax={maxValue}
                                aria-label={`${item.label}: ${valueFormatter(item.value)}`}
                            />
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}
