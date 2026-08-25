<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\Analytics\AnalyticsFilters;
use App\Support\Analytics\AnalyticsSql;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin analytics aggregations over orders / payments / line items.
 *
 * Heavy endpoints (prefer narrow date ranges in production):
 * - overview: dual-period KPI bundles + status distributions
 * - customers: distinct user_id scans + first-order cohort intersect
 * - products: full order_items rollups (products / variants / categories / add-ons)
 * - exportCsv: streamed chunks; still expensive for wide date ranges
 *
 * Metric formulas must stay stable — optimize with indexes / eager loads only.
 */
class AnalyticsService
{
    /**
     * Dual-period KPIs — runs two full order + payment aggregate passes.
     *
     * @return array<string, mixed>
     */
    public function overview(AnalyticsFilters $filters): array
    {
        $current = $this->kpiBundle($filters);
        $previousFilters = new AnalyticsFilters(
            period: $filters->period->previousEquivalent(),
            branchId: $filters->branchId,
            type: $filters->type,
            status: $filters->status,
            paymentStatus: $filters->paymentStatus,
            paymentMethod: $filters->paymentMethod,
            deliveryZoneId: $filters->deliveryZoneId,
            categoryId: $filters->categoryId,
            productId: $filters->productId,
        );
        $previous = $this->kpiBundle($previousFilters);

        return [
            'filters' => $filters->meta(),
            'kpis' => $current,
            'comparison' => [
                'revenue' => AnalyticsSql::delta((float) $current['net_revenue'], (float) $previous['net_revenue']),
                'orders' => AnalyticsSql::delta((float) $current['total_orders'], (float) $previous['total_orders']),
                'aov' => AnalyticsSql::delta((float) $current['average_order_value'], (float) $previous['average_order_value']),
                'previous' => $previous,
            ],
            'order_status_distribution' => $this->orderStatusDistribution($filters),
            'payment_status_distribution' => $this->paymentStatusDistribution($filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sales(AnalyticsFilters $filters): array
    {
        $grain = $filters->grain ?? $filters->period->suggestedGrain();
        $bucket = AnalyticsSql::bucketExpression('orders.created_at', $grain);

        $rows = $this->baseOrderQuery($filters)
            ->selectRaw("{$bucket} as bucket")
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN orders.grand_total ELSE 0 END) as revenue', [
                OrderStatus::Cancelled->value,
            ])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'filters' => $filters->meta(),
            'grain' => $grain,
            'series' => $rows->map(fn ($row) => [
                'bucket' => (string) $row->bucket,
                'orders' => (int) $row->orders_count,
                'revenue' => AnalyticsSql::money($row->revenue),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function orders(AnalyticsFilters $filters): array
    {
        return [
            'filters' => $filters->meta(),
            'by_status' => $this->orderStatusDistribution($filters),
            'by_type' => $this->orderTypeBreakdown($filters),
            'peak_hours' => $this->peakHours($filters),
            'day_of_week' => $this->dayOfWeek($filters),
            'cancellations' => $this->cancellations($filters),
        ];
    }

    /**
     * Full merchandise rollups across order_items (and add-ons) — heavy for large catalogs/history.
     *
     * @return array<string, mixed>
     */
    public function products(AnalyticsFilters $filters): array
    {
        return [
            'filters' => $filters->meta(),
            'products' => $this->productPerformance($filters),
            'variants' => $this->variantPerformance($filters),
            'categories' => $this->categoryPerformance($filters),
            'add_ons' => $this->addOnPerformance($filters),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function branches(AnalyticsFilters $filters): array
    {
        $rows = $this->baseOrderQuery($filters)
            ->leftJoin('branches', 'branches.id', '=', 'orders.branch_id')
            ->selectRaw('orders.branch_id')
            ->selectRaw('COALESCE(branches.name, ?) as branch_name', ['Unknown branch'])
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN orders.grand_total ELSE 0 END) as revenue', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status = ? THEN 1 ELSE 0 END) as completed_orders', [
                OrderStatus::Completed->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status = ? THEN 1 ELSE 0 END) as cancelled_orders', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN 1 ELSE 0 END) as eligible_orders', [
                OrderStatus::Cancelled->value,
            ])
            ->groupBy('orders.branch_id', 'branches.name')
            ->get();

        $paymentSuccess = $this->paymentSuccessByBranch($filters);

        $mapped = $rows->map(function ($row) use ($paymentSuccess) {
            $eligible = (int) $row->eligible_orders;
            $revenue = (float) $row->revenue;
            $verified = (int) ($paymentSuccess[(int) $row->branch_id]['verified'] ?? 0);
            $paymentTotal = (int) ($paymentSuccess[(int) $row->branch_id]['total'] ?? 0);

            return [
                'branch_id' => $row->branch_id,
                'branch' => $row->branch_name,
                'orders' => (int) $row->orders_count,
                'revenue' => AnalyticsSql::money($revenue),
                'average_order_value' => AnalyticsSql::aov($revenue, $eligible),
                'completed_orders' => (int) $row->completed_orders,
                'cancelled_orders' => (int) $row->cancelled_orders,
                'payment_success_rate' => AnalyticsSql::rate($verified, $paymentTotal),
            ];
        });

        $sorted = match ($filters->branchSort) {
            'most_orders' => $mapped->sortByDesc('orders'),
            'highest_aov' => $mapped->sortByDesc(fn ($row) => (float) $row['average_order_value']),
            default => $mapped->sortByDesc(fn ($row) => (float) $row['revenue']),
        };

        return [
            'filters' => $filters->meta(),
            'branches' => $sorted->values()->all(),
        ];
    }

    /**
     * Customer cohorts — plucks distinct user ids for the period; keep filters narrow.
     *
     * @return array<string, mixed>
     */
    public function customers(AnalyticsFilters $filters): array
    {
        $orderStats = $this->baseOrderQuery($filters)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('COUNT(DISTINCT orders.user_id) as customers_with_orders')
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN orders.grand_total ELSE 0 END) as revenue', [
                OrderStatus::Cancelled->value,
            ])
            ->first();

        $totalCustomersQuery = User::query()->where('role', UserRole::User->value);

        if ($filters->branchId !== null) {
            $totalCustomersQuery->whereHas(
                'orders',
                fn ($query) => $query->where('branch_id', $filters->branchId),
            );
        }

        $totalCustomers = $totalCustomersQuery->count();

        $newCustomerIdsQuery = DB::table('orders')->select('user_id');

        if ($filters->branchId !== null) {
            $newCustomerIdsQuery->where('branch_id', $filters->branchId);
        }

        $newCustomerIds = $newCustomerIdsQuery
            ->groupBy('user_id')
            ->havingRaw('MIN(created_at) >= ?', [$filters->period->from])
            ->havingRaw('MIN(created_at) < ?', [$filters->period->toExclusive])
            ->pluck('user_id');

        $customersWithOrdersInPeriod = $this->baseOrderQuery($filters)
            ->distinct()
            ->pluck('orders.user_id');

        $newInPeriod = $customersWithOrdersInPeriod->intersect($newCustomerIds)->count();
        $returningInPeriod = $customersWithOrdersInPeriod->diff($newCustomerIds)->count();

        $customersWithOrders = (int) ($orderStats->customers_with_orders ?? 0);
        $totalOrders = (int) ($orderStats->total_orders ?? 0);
        $revenue = (float) ($orderStats->revenue ?? 0);

        $topCustomers = $this->baseOrderQuery($filters)
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->selectRaw('orders.user_id')
            ->selectRaw('users.name as customer_name')
            ->selectRaw('users.email as customer_email')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN orders.grand_total ELSE 0 END) as total_spend', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('MAX(orders.created_at) as last_order_at')
            ->groupBy('orders.user_id', 'users.name', 'users.email')
            ->orderByDesc('total_spend')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                $orders = (int) $row->orders_count;
                $spend = (float) $row->total_spend;

                return [
                    'customer' => $row->customer_name,
                    'email' => $row->customer_email,
                    'orders' => $orders,
                    'total_spend' => AnalyticsSql::money($spend),
                    'average_order_value' => AnalyticsSql::aov($spend, $orders),
                    'last_order_at' => $row->last_order_at,
                ];
            })
            ->values()
            ->all();

        $newOrders = $this->baseOrderQuery($filters)
            ->whereIn('orders.user_id', $newCustomerIds)
            ->count();

        $returningOrders = max(0, $totalOrders - $newOrders);

        return [
            'filters' => $filters->meta(),
            'kpis' => [
                'total_customers' => $totalCustomers,
                'customers_with_orders' => $customersWithOrders,
                'new_customers' => $newInPeriod,
                'returning_customers' => $returningInPeriod,
                'average_orders_per_customer' => $customersWithOrders === 0
                    ? '0.00'
                    : number_format($totalOrders / $customersWithOrders, 2, '.', ''),
                'average_spend_per_customer' => AnalyticsSql::aov($revenue, $customersWithOrders),
                'new_customer_orders' => $newOrders,
                'returning_customer_orders' => $returningOrders,
            ],
            'top_customers' => $topCustomers,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentMethods(AnalyticsFilters $filters): array
    {
        $rows = Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->tap(fn ($query) => $filters->applyToOrders($query, 'orders'))
            ->selectRaw('payments.method')
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('SUM(payments.amount) as revenue')
            ->groupBy('payments.method')
            ->get();

        $totalRevenue = (float) $rows->sum('revenue');

        return [
            'filters' => $filters->meta(),
            'methods' => $rows->map(function ($row) use ($totalRevenue) {
                $revenue = (float) $row->revenue;

                return [
                    'method' => $row->method instanceof \BackedEnum ? $row->method->value : (string) $row->method,
                    'transaction_count' => (int) $row->transaction_count,
                    'revenue' => AnalyticsSql::money($revenue),
                    'percentage' => $totalRevenue == 0.0
                        ? '0.00'
                        : number_format(($revenue / $totalRevenue) * 100, 2, '.', ''),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deliveryZones(AnalyticsFilters $filters): array
    {
        $rows = $this->baseOrderQuery($filters)
            ->where('orders.type', 'delivery')
            ->selectRaw('orders.delivery_zone_id')
            ->selectRaw('COALESCE(orders.delivery_zone_name, ?) as zone_name', ['Unknown zone'])
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN orders.grand_total ELSE 0 END) as revenue', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN orders.delivery_fee ELSE 0 END) as delivery_fees', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN 1 ELSE 0 END) as eligible_orders', [
                OrderStatus::Cancelled->value,
            ])
            ->groupBy('orders.delivery_zone_id', 'orders.delivery_zone_name')
            ->orderByDesc('revenue')
            ->get();

        return [
            'filters' => $filters->meta(),
            'zones' => $rows->map(function ($row) {
                $eligible = (int) $row->eligible_orders;
                $revenue = (float) $row->revenue;

                return [
                    'delivery_zone_id' => $row->delivery_zone_id,
                    'zone' => $row->zone_name,
                    'orders' => (int) $row->orders_count,
                    'revenue' => AnalyticsSql::money($revenue),
                    'delivery_fees' => AnalyticsSql::money($row->delivery_fees),
                    'average_order_value' => AnalyticsSql::aov($revenue, $eligible),
                ];
            })->values()->all(),
            'note' => 'Zone names and delivery fees are taken from historical order snapshots, not current zone configuration.',
        ];
    }

    /**
     * Streamed CSV export — uses eager-loaded relations for order rows; still heavy for wide ranges.
     */
    public function exportCsv(AnalyticsFilters $filters): StreamedResponse
    {
        $report = $filters->report;
        $filename = 'pebbles-analytics-'.$report.'-'.$filters->period->from->toDateString().'.csv';

        return response()->streamDownload(function () use ($filters, $report): void {
            $handle = fopen('php://output', 'w');

            match ($report) {
                'products' => $this->writeProductCsv($handle, $filters),
                'branches' => $this->writeBranchCsv($handle, $filters),
                'payments' => $this->writePaymentCsv($handle, $filters),
                default => $this->writeOrdersCsv($handle, $filters),
            };

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function kpiBundle(AnalyticsFilters $filters): array
    {
        $orderRow = $this->baseOrderQuery($filters)
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(CASE WHEN orders.status = ? THEN 1 ELSE 0 END) as completed_orders', [
                OrderStatus::Completed->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status = ? THEN 1 ELSE 0 END) as cancelled_orders', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN 1 ELSE 0 END) as eligible_orders', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN orders.grand_total ELSE 0 END) as net_revenue', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN (orders.grand_total + orders.discount_amount) ELSE 0 END) as gross_revenue', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status = ? THEN orders.grand_total ELSE 0 END) as cancellation_revenue', [
                OrderStatus::Cancelled->value,
            ])
            ->first();

        $paymentRow = Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->tap(fn ($query) => $filters->applyToOrders($query, 'orders'))
            ->selectRaw('SUM(CASE WHEN payments.status = ? THEN 1 ELSE 0 END) as successful_payments', [
                PaymentStatus::Verified->value,
            ])
            ->selectRaw('SUM(CASE WHEN payments.status = ? THEN 1 ELSE 0 END) as rejected_payments', [
                PaymentStatus::Rejected->value,
            ])
            ->selectRaw('SUM(CASE WHEN payments.status = ? THEN 1 ELSE 0 END) as failed_payments', [
                PaymentStatus::Failed->value,
            ])
            ->selectRaw('SUM(CASE WHEN payments.status IN (?, ?) THEN 1 ELSE 0 END) as pending_like_payments', [
                PaymentStatus::Pending->value,
                PaymentStatus::Submitted->value,
            ])
            ->selectRaw('COUNT(*) as total_payments')
            ->first();

        $net = (float) ($orderRow->net_revenue ?? 0);
        $eligible = (int) ($orderRow->eligible_orders ?? 0);
        $successful = (int) ($paymentRow->successful_payments ?? 0);
        $totalPayments = (int) ($paymentRow->total_payments ?? 0);
        $rejected = (int) ($paymentRow->rejected_payments ?? 0);

        return [
            'total_revenue' => AnalyticsSql::money($orderRow->gross_revenue ?? 0),
            'gross_revenue' => AnalyticsSql::money($orderRow->gross_revenue ?? 0),
            'net_revenue' => AnalyticsSql::money($net),
            'total_orders' => (int) ($orderRow->total_orders ?? 0),
            'completed_orders' => (int) ($orderRow->completed_orders ?? 0),
            'cancelled_orders' => (int) ($orderRow->cancelled_orders ?? 0),
            'average_order_value' => AnalyticsSql::aov($net, $eligible),
            'successful_payments' => $successful,
            'rejected_payments' => $rejected,
            'failed_payments' => (int) ($paymentRow->failed_payments ?? 0),
            'pending_payments' => (int) ($paymentRow->pending_like_payments ?? 0),
            'payment_success_rate' => AnalyticsSql::rate($successful, $totalPayments),
            'payment_rejection_rate' => AnalyticsSql::rate($rejected, $totalPayments),
            'cancellation_rate' => AnalyticsSql::rate(
                (int) ($orderRow->cancelled_orders ?? 0),
                (int) ($orderRow->total_orders ?? 0),
            ),
            'cancellation_revenue' => AnalyticsSql::money($orderRow->cancellation_revenue ?? 0),
        ];
    }

    /**
     * @return list<array{status: string, count: int}>
     */
    private function orderStatusDistribution(AnalyticsFilters $filters): array
    {
        $counts = $this->baseOrderQuery($filters)
            ->selectRaw('orders.status')
            ->selectRaw('COUNT(*) as aggregate_count')
            ->groupBy('orders.status')
            ->pluck('aggregate_count', 'status');

        return collect(OrderStatus::cases())->map(fn (OrderStatus $status) => [
            'status' => $status->value,
            'count' => (int) ($counts[$status->value] ?? 0),
        ])->values()->all();
    }

    /**
     * @return list<array{status: string, count: int}>
     */
    private function paymentStatusDistribution(AnalyticsFilters $filters): array
    {
        $counts = Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->tap(fn ($query) => $filters->applyToOrders($query, 'orders'))
            ->selectRaw('payments.status')
            ->selectRaw('COUNT(*) as aggregate_count')
            ->groupBy('payments.status')
            ->pluck('aggregate_count', 'status');

        return collect(PaymentStatus::cases())->map(fn (PaymentStatus $status) => [
            'status' => $status->value,
            'count' => (int) ($counts[$status->value] ?? 0),
        ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function orderTypeBreakdown(AnalyticsFilters $filters): array
    {
        $rows = $this->baseOrderQuery($filters)
            ->selectRaw('orders.type')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN orders.grand_total ELSE 0 END) as revenue', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN 1 ELSE 0 END) as eligible_orders', [
                OrderStatus::Cancelled->value,
            ])
            ->groupBy('orders.type')
            ->get();

        $totalOrders = (int) $rows->sum('orders_count');

        return $rows->map(function ($row) use ($totalOrders) {
            $eligible = (int) $row->eligible_orders;
            $revenue = (float) $row->revenue;
            $orders = (int) $row->orders_count;

            return [
                'type' => $row->type instanceof \BackedEnum ? $row->type->value : (string) $row->type,
                'orders' => $orders,
                'revenue' => AnalyticsSql::money($revenue),
                'average_order_value' => AnalyticsSql::aov($revenue, $eligible),
                'percentage' => AnalyticsSql::rate($orders, $totalOrders),
            ];
        })->values()->all();
    }

    /**
     * @return list<array{hour: int, label: string, orders: int, revenue: string}>
     */
    private function peakHours(AnalyticsFilters $filters): array
    {
        $bucket = AnalyticsSql::bucketExpression('orders.created_at', 'hour');
        $rows = $this->baseOrderQuery($filters)
            ->selectRaw("{$bucket} as hour_bucket")
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN orders.grand_total ELSE 0 END) as revenue', [
                OrderStatus::Cancelled->value,
            ])
            ->groupBy('hour_bucket')
            ->get()
            ->keyBy(fn ($row) => (int) $row->hour_bucket);

        return collect(range(0, 23))->map(function (int $hour) use ($rows) {
            $row = $rows->get($hour);

            return [
                'hour' => $hour,
                'label' => sprintf('%02d:00', $hour),
                'orders' => (int) ($row->orders_count ?? 0),
                'revenue' => AnalyticsSql::money($row->revenue ?? 0),
            ];
        })->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dayOfWeek(AnalyticsFilters $filters): array
    {
        $bucket = AnalyticsSql::bucketExpression('orders.created_at', 'dow');
        $labels = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        $rows = $this->baseOrderQuery($filters)
            ->selectRaw("{$bucket} as dow_bucket")
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN orders.grand_total ELSE 0 END) as revenue', [
                OrderStatus::Cancelled->value,
            ])
            ->selectRaw('SUM(CASE WHEN orders.status != ? THEN 1 ELSE 0 END) as eligible_orders', [
                OrderStatus::Cancelled->value,
            ])
            ->groupBy('dow_bucket')
            ->get()
            ->keyBy(fn ($row) => (int) $row->dow_bucket);

        return collect(range(0, 6))->map(function (int $dow) use ($rows, $labels) {
            $row = $rows->get($dow);
            $orders = (int) ($row->orders_count ?? 0);
            $eligible = (int) ($row->eligible_orders ?? 0);
            $revenue = (float) ($row->revenue ?? 0);

            return [
                'day' => $labels[$dow],
                'day_index' => $dow,
                'orders' => $orders,
                'revenue' => AnalyticsSql::money($revenue),
                'average_order_value' => AnalyticsSql::aov($revenue, $eligible),
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function cancellations(AnalyticsFilters $filters): array
    {
        $cancelledFilters = new AnalyticsFilters(
            period: $filters->period,
            branchId: $filters->branchId,
            type: $filters->type,
            status: OrderStatus::Cancelled,
            paymentStatus: $filters->paymentStatus,
            paymentMethod: $filters->paymentMethod,
            deliveryZoneId: $filters->deliveryZoneId,
            categoryId: $filters->categoryId,
            productId: $filters->productId,
        );

        $total = (int) $this->baseOrderQuery($filters)->count();
        $cancelled = (int) $this->baseOrderQuery($cancelledFilters)->count();
        $cancellationRevenue = (float) $this->baseOrderQuery($cancelledFilters)->sum('orders.grand_total');

        $byBranch = $this->baseOrderQuery($cancelledFilters)
            ->leftJoin('branches', 'branches.id', '=', 'orders.branch_id')
            ->selectRaw('COALESCE(branches.name, ?) as branch_name', ['Unknown branch'])
            ->selectRaw('COUNT(*) as aggregate_count')
            ->groupBy('branches.name')
            ->get()
            ->map(fn ($row) => [
                'branch' => $row->branch_name,
                'count' => (int) $row->aggregate_count,
            ])->values()->all();

        $byType = $this->baseOrderQuery($cancelledFilters)
            ->selectRaw('orders.type')
            ->selectRaw('COUNT(*) as aggregate_count')
            ->groupBy('orders.type')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type instanceof \BackedEnum ? $row->type->value : (string) $row->type,
                'count' => (int) $row->aggregate_count,
            ])->values()->all();

        $reasons = $this->baseOrderQuery($cancelledFilters)
            ->whereNotNull('orders.cancellation_reason')
            ->where('orders.cancellation_reason', '!=', '')
            ->selectRaw('orders.cancellation_reason as reason')
            ->selectRaw('COUNT(*) as aggregate_count')
            ->groupBy('orders.cancellation_reason')
            ->orderByDesc('aggregate_count')
            ->get();

        return [
            'cancelled_orders' => $cancelled,
            'cancellation_rate' => AnalyticsSql::rate($cancelled, $total),
            'cancellation_revenue' => AnalyticsSql::money($cancellationRevenue),
            'by_branch' => $byBranch,
            'by_type' => $byType,
            'reasons' => $reasons->isEmpty()
                ? []
                : $reasons->map(fn ($row) => [
                    'reason' => $row->reason,
                    'count' => (int) $row->aggregate_count,
                    'percentage' => AnalyticsSql::rate((int) $row->aggregate_count, $cancelled),
                ])->values()->all(),
            'reasons_note' => $reasons->isEmpty()
                ? 'Cancellation reasons are not recorded for these orders.'
                : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productPerformance(AnalyticsFilters $filters): array
    {
        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->tap(fn ($q) => $filters->applyToOrders($q, 'orders'))
            ->selectRaw('COALESCE(order_items.product_name, ?) as product', ['Unknown product'])
            ->selectRaw('order_items.product_id')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_containing')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name');

        $rows = $query->get();

        $mapped = $rows->map(function ($row) {
            $units = (int) $row->units_sold;
            $revenue = (float) $row->revenue;

            return [
                'product_id' => $row->product_id,
                'product' => $row->product,
                'units_sold' => $units,
                'orders_containing' => (int) $row->orders_containing,
                'revenue' => AnalyticsSql::money($revenue),
                'average_selling_price' => AnalyticsSql::aov($revenue, $units),
            ];
        });

        $sorted = match ($filters->productSort) {
            'best_selling' => $mapped->sortByDesc('units_sold'),
            'lowest_selling' => $mapped->sortBy('units_sold'),
            default => $mapped->sortByDesc(fn ($row) => (float) $row['revenue']),
        };

        return $sorted->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function variantPerformance(AnalyticsFilters $filters): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->whereNotNull('order_items.variant_name')
            ->where('order_items.variant_name', '!=', '')
            ->tap(fn ($q) => $filters->applyToOrders($q, 'orders'))
            ->selectRaw('order_items.product_name')
            ->selectRaw('order_items.variant_name as variant')
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_name', 'order_items.variant_name')
            ->orderByDesc('revenue')
            ->limit(50)
            ->get()
            ->map(fn ($row) => [
                'product' => $row->product_name,
                'variant' => $row->variant,
                'units_sold' => (int) $row->units_sold,
                'revenue' => AnalyticsSql::money($row->revenue),
            ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function categoryPerformance(AnalyticsFilters $filters): array
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->tap(fn ($q) => $filters->applyToOrders($q, 'orders'))
            ->selectRaw('COALESCE(categories.name, ?) as category', ['Uncategorized'])
            ->selectRaw('SUM(order_items.quantity) as units_sold')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as order_count')
            ->groupBy('categories.name')
            ->get();

        $totalRevenue = (float) $rows->sum('revenue');

        return $rows->map(function ($row) use ($totalRevenue) {
            $revenue = (float) $row->revenue;

            return [
                'category' => $row->category,
                'units_sold' => (int) $row->units_sold,
                'revenue' => AnalyticsSql::money($revenue),
                'order_count' => (int) $row->order_count,
                'percentage_of_revenue' => $totalRevenue == 0.0
                    ? '0.00'
                    : number_format(($revenue / $totalRevenue) * 100, 2, '.', ''),
            ];
        })->sortByDesc(fn ($row) => (float) $row['revenue'])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function addOnPerformance(AnalyticsFilters $filters): array
    {
        return DB::table('order_item_add_ons')
            ->join('order_items', 'order_items.id', '=', 'order_item_add_ons.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->tap(fn ($q) => $filters->applyToOrders($q, 'orders'))
            ->selectRaw('COALESCE(order_item_add_ons.add_on_name, ?) as add_on', ['Unknown add-on'])
            ->selectRaw('SUM(order_item_add_ons.quantity) as times_selected')
            ->selectRaw('SUM(order_item_add_ons.line_total) as revenue_contribution')
            ->groupBy('order_item_add_ons.add_on_name')
            ->orderByDesc('revenue_contribution')
            ->get()
            ->map(fn ($row) => [
                'add_on' => $row->add_on,
                'times_selected' => (int) $row->times_selected,
                'revenue_contribution' => AnalyticsSql::money($row->revenue_contribution),
            ])->values()->all();
    }

    /**
     * @return array<int, array{verified: int, total: int}>
     */
    private function paymentSuccessByBranch(AnalyticsFilters $filters): array
    {
        return Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->tap(fn ($query) => $filters->applyToOrders($query, 'orders'))
            ->selectRaw('orders.branch_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN payments.status = ? THEN 1 ELSE 0 END) as verified', [
                PaymentStatus::Verified->value,
            ])
            ->groupBy('orders.branch_id')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (int) $row->branch_id => [
                    'verified' => (int) $row->verified,
                    'total' => (int) $row->total,
                ],
            ])
            ->all();
    }

    /**
     * @return Builder<Order>
     */
    private function baseOrderQuery(AnalyticsFilters $filters)
    {
        return Order::query()->tap(fn ($query) => $filters->applyToOrders($query, 'orders'));
    }

    /**
     * @param  resource  $handle
     */
    private function writeOrdersCsv($handle, AnalyticsFilters $filters): void
    {
        fputcsv($handle, [
            'Date',
            'Order Number',
            'Branch',
            'Order Type',
            'Status',
            'Payment Status',
            'Customer',
            'Subtotal',
            'Discount',
            'Delivery Fee',
            'Total',
        ]);

        $this->baseOrderQuery($filters)
            ->with(['branch', 'user', 'payment'])
            ->orderBy('orders.created_at')
            ->chunk(200, function (Collection $orders) use ($handle): void {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        optional($order->created_at)?->toDateTimeString(),
                        $order->public_order_number,
                        $order->branch?->name,
                        $order->type?->value,
                        $order->status?->value,
                        $order->payment?->status?->value,
                        $order->user?->name,
                        $order->subtotal,
                        $order->discount_amount,
                        $order->delivery_fee,
                        $order->grand_total,
                    ]);
                }
            });
    }

    /**
     * @param  resource  $handle
     */
    private function writeProductCsv($handle, AnalyticsFilters $filters): void
    {
        fputcsv($handle, ['Product', 'Units Sold', 'Orders Containing', 'Revenue', 'Average Selling Price']);

        foreach ($this->productPerformance($filters) as $row) {
            fputcsv($handle, [
                $row['product'],
                $row['units_sold'],
                $row['orders_containing'],
                $row['revenue'],
                $row['average_selling_price'],
            ]);
        }
    }

    /**
     * @param  resource  $handle
     */
    private function writeBranchCsv($handle, AnalyticsFilters $filters): void
    {
        fputcsv($handle, ['Branch', 'Orders', 'Revenue', 'AOV', 'Completed', 'Cancelled', 'Payment Success Rate']);

        foreach ($this->branches($filters)['branches'] as $row) {
            fputcsv($handle, [
                $row['branch'],
                $row['orders'],
                $row['revenue'],
                $row['average_order_value'],
                $row['completed_orders'],
                $row['cancelled_orders'],
                $row['payment_success_rate'],
            ]);
        }
    }

    /**
     * @param  resource  $handle
     */
    private function writePaymentCsv($handle, AnalyticsFilters $filters): void
    {
        fputcsv($handle, ['Method', 'Transactions', 'Revenue', 'Percentage']);

        foreach ($this->paymentMethods($filters)['methods'] as $row) {
            fputcsv($handle, [
                $row['method'],
                $row['transaction_count'],
                $row['revenue'],
                $row['percentage'],
            ]);
        }
    }
}
