<?php

namespace App\Support\Analytics;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class AnalyticsFilters
{
    /**
     * @param  array{
     *     period: AnalyticsPeriod,
     *     branch_id?: int|null,
     *     type?: string|null,
     *     status?: string|null,
     *     payment_status?: string|null,
     *     payment_method?: string|null,
     *     delivery_zone_id?: int|null,
     *     category_id?: int|null,
     *     product_id?: int|null,
     *     product_sort?: string|null,
     *     branch_sort?: string|null,
     *     grain?: string|null,
     *     report?: string|null
     * }  $data
     */
    public function __construct(
        public readonly AnalyticsPeriod $period,
        public readonly ?int $branchId = null,
        public readonly ?OrderType $type = null,
        public readonly ?OrderStatus $status = null,
        public readonly ?PaymentStatus $paymentStatus = null,
        public readonly ?PaymentMethod $paymentMethod = null,
        public readonly ?int $deliveryZoneId = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $productId = null,
        public readonly string $productSort = 'highest_revenue',
        public readonly string $branchSort = 'highest_revenue',
        public readonly ?string $grain = null,
        public readonly string $report = 'orders',
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $period = AnalyticsPeriod::fromInput([
            'preset' => $validated['preset'] ?? 'last_30_days',
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ]);

        return new self(
            period: $period,
            branchId: isset($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            type: isset($validated['type']) ? OrderType::from($validated['type']) : null,
            status: isset($validated['status']) ? OrderStatus::from($validated['status']) : null,
            paymentStatus: isset($validated['payment_status']) ? PaymentStatus::from($validated['payment_status']) : null,
            paymentMethod: isset($validated['payment_method']) ? PaymentMethod::from($validated['payment_method']) : null,
            deliveryZoneId: isset($validated['delivery_zone_id']) ? (int) $validated['delivery_zone_id'] : null,
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            productId: isset($validated['product_id']) ? (int) $validated['product_id'] : null,
            productSort: $validated['product_sort'] ?? 'highest_revenue',
            branchSort: $validated['branch_sort'] ?? 'highest_revenue',
            grain: $validated['grain'] ?? null,
            report: $validated['report'] ?? 'orders',
        );
    }

    /**
     * @param  Builder<Order>|QueryBuilder  $query
     * @return Builder<Order>|QueryBuilder
     */
    public function applyToOrders(Builder|QueryBuilder $query, string $table = 'orders'): Builder|QueryBuilder
    {
        $query
            ->where("{$table}.created_at", '>=', $this->period->from)
            ->where("{$table}.created_at", '<', $this->period->toExclusive);

        if ($this->branchId !== null) {
            $query->where("{$table}.branch_id", $this->branchId);
        }

        if ($this->type !== null) {
            $query->where("{$table}.type", $this->type->value);
        }

        if ($this->status !== null) {
            $query->where("{$table}.status", $this->status->value);
        }

        if ($this->deliveryZoneId !== null) {
            $query->where("{$table}.delivery_zone_id", $this->deliveryZoneId);
        }

        if ($this->paymentStatus !== null || $this->paymentMethod !== null) {
            $query->whereExists(function (QueryBuilder $paymentQuery): void {
                $paymentQuery->selectRaw('1')
                    ->from('payments')
                    ->whereColumn('payments.order_id', 'orders.id');

                if ($this->paymentStatus !== null) {
                    $paymentQuery->where('payments.status', $this->paymentStatus->value);
                }

                if ($this->paymentMethod !== null) {
                    $paymentQuery->where('payments.method', $this->paymentMethod->value);
                }
            });
        }

        if ($this->productId !== null || $this->categoryId !== null) {
            $query->whereExists(function (QueryBuilder $itemQuery): void {
                $itemQuery->selectRaw('1')
                    ->from('order_items')
                    ->whereColumn('order_items.order_id', 'orders.id');

                if ($this->productId !== null) {
                    $itemQuery->where('order_items.product_id', $this->productId);
                }

                if ($this->categoryId !== null) {
                    $itemQuery->join('products', 'products.id', '=', 'order_items.product_id')
                        ->where('products.category_id', $this->categoryId);
                }
            });
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return [
            'preset' => $this->period->preset,
            'from' => $this->period->from->toDateString(),
            'to' => $this->period->toExclusive->subDay()->toDateString(),
            'from_at' => $this->period->from->toIso8601String(),
            'to_exclusive_at' => $this->period->toExclusive->toIso8601String(),
            'grain' => $this->grain ?? $this->period->suggestedGrain(),
            'branch_id' => $this->branchId,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'payment_status' => $this->paymentStatus?->value,
            'payment_method' => $this->paymentMethod?->value,
            'delivery_zone_id' => $this->deliveryZoneId,
            'category_id' => $this->categoryId,
            'product_id' => $this->productId,
        ];
    }
}
