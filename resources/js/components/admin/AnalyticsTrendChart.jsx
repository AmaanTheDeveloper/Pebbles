function safeNumber(value) {
    const parsed = Number(value ?? 0);

    return Number.isFinite(parsed) ? parsed : 0;
}

function buildPoints(values, max, width, height, padding) {
    const innerWidth = width - padding * 2;
    const innerHeight = height - padding * 2;
    const count = values.length;

    if (count === 0) {
        return '';
    }

    if (count === 1) {
        const x = padding + innerWidth / 2;
        const y = padding + innerHeight - (values[0] / max) * innerHeight;

        return `${x},${y}`;
    }

    return values
        .map((value, index) => {
            const x = padding + (index / (count - 1)) * innerWidth;
            const y = padding + innerHeight - (value / max) * innerHeight;

            return `${x},${y}`;
        })
        .join(' ');
}

export default function AnalyticsTrendChart({
    series = [],
    emptyMessage = 'No sales data for this period.',
    grain,
}) {
    const points = series.map((row) => ({
        bucket: row.bucket ?? '',
        orders: safeNumber(row.orders),
        revenue: safeNumber(row.revenue),
    }));

    const hasData = points.some((row) => row.orders > 0 || row.revenue > 0);

    if (!points.length || !hasData) {
        return <p className="text-sm text-stone-500">{emptyMessage}</p>;
    }

    const maxOrders = Math.max(...points.map((row) => row.orders), 1);
    const maxRevenue = Math.max(...points.map((row) => row.revenue), 1);

    const width = 100;
    const height = 40;
    const padding = 2;
    const innerWidth = width - padding * 2;
    const innerHeight = height - padding * 2;
    const barWidth = innerWidth / points.length;

    const revenuePoints = buildPoints(
        points.map((row) => row.revenue),
        maxRevenue,
        width,
        height,
        padding,
    );

    const areaPoints = `${padding},${height - padding} ${revenuePoints} ${width - padding},${height - padding}`;

    return (
        <div className="admin-chart-panel space-y-3">
            {grain ? (
                <p className="text-xs font-medium tracking-wide text-stone-500 uppercase">Grain: {grain}</p>
            ) : null}
            <div className="overflow-x-auto">
                <svg viewBox={`0 0 ${width} ${height + 6}`} className="h-44 w-full min-w-[320px]" role="img" aria-label="Sales trend chart">
                    <polygon points={areaPoints} className="fill-amber-200/60" />
                    <polyline
                        points={revenuePoints}
                        fill="none"
                        className="stroke-amber-800"
                        strokeWidth="0.6"
                        strokeLinejoin="round"
                        strokeLinecap="round"
                    />
                    {points.map((row, index) => {
                        const barHeight = (row.orders / maxOrders) * innerHeight * 0.35;
                        const x = padding + index * barWidth + barWidth * 0.2;
                        const w = barWidth * 0.6;

                        return (
                            <rect
                                key={`${row.bucket}-${index}`}
                                x={x}
                                y={height - padding - barHeight}
                                width={w}
                                height={barHeight || 0}
                                className="fill-stone-400/70"
                                rx="0.3"
                            >
                                <title>{`${row.bucket}: ${row.orders} orders, Rs ${row.revenue.toLocaleString()}`}</title>
                            </rect>
                        );
                    })}
                </svg>
            </div>
            <div className="flex flex-wrap items-center gap-4 text-xs text-stone-600">
                <span className="inline-flex items-center gap-1.5">
                    <span className="inline-block h-0.5 w-4 rounded bg-amber-800" aria-hidden="true" />
                    Revenue
                </span>
                <span className="inline-flex items-center gap-1.5">
                    <span className="inline-block h-2 w-2 rounded-sm bg-stone-400/70" aria-hidden="true" />
                    Orders
                </span>
            </div>
            <div
                className="grid gap-1 text-[10px] text-stone-500"
                style={{ gridTemplateColumns: `repeat(${Math.min(points.length, 12)}, minmax(0, 1fr))` }}
            >
                {points
                    .filter((_, index) => points.length <= 12 || index % Math.ceil(points.length / 12) === 0)
                    .map((row, index) => (
                        <span key={`${row.bucket}-axis-${index}`} className="truncate" title={row.bucket}>
                            {row.bucket}
                        </span>
                    ))}
            </div>
        </div>
    );
}
