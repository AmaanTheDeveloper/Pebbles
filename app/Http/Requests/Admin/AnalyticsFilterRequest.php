<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\AdminBranchScope;
use App\Support\Analytics\AnalyticsFilters;
use App\Support\Analytics\AnalyticsPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AnalyticsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'preset' => ['sometimes', 'nullable', Rule::in(AnalyticsPeriod::PRESETS)],
            'from' => ['required_if:preset,custom', 'nullable', 'date'],
            'to' => ['required_if:preset,custom', 'nullable', 'date', 'after_or_equal:from'],
            'branch_id' => ['sometimes', 'nullable', 'integer', 'exists:branches,id'],
            'type' => ['sometimes', 'nullable', Rule::enum(OrderType::class)],
            'status' => ['sometimes', 'nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['sometimes', 'nullable', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['sometimes', 'nullable', Rule::enum(PaymentMethod::class)],
            'delivery_zone_id' => ['sometimes', 'nullable', 'integer', 'exists:delivery_zones,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'product_id' => ['sometimes', 'nullable', 'integer', 'exists:products,id'],
            'product_sort' => ['sometimes', 'nullable', Rule::in(['best_selling', 'highest_revenue', 'lowest_selling'])],
            'branch_sort' => ['sometimes', 'nullable', Rule::in(['highest_revenue', 'most_orders', 'highest_aov'])],
            'grain' => ['sometimes', 'nullable', Rule::in(['day', 'week', 'month'])],
            'report' => ['sometimes', 'nullable', Rule::in(['orders', 'products', 'branches', 'payments', 'overview'])],
        ];
    }

    public function filters(): AnalyticsFilters
    {
        try {
            $validated = $this->validated();
            $validated['branch_id'] = AdminBranchScope::resolveBranchId(
                $this->user(),
                $validated['branch_id'] ?? null,
            );

            return AnalyticsFilters::fromValidated($validated);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'preset' => $exception->getMessage(),
            ]);
        }
    }
}
