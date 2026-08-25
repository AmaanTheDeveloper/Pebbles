<?php

namespace App\Http\Requests\Admin;

use App\Enums\CouponDiscountType;
use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('code')) {
            $this->merge(['code' => Coupon::normalizeCode($this->input('code'))]);
        }

        foreach (['minimum_order_amount', 'maximum_discount_amount', 'usage_limit', 'per_customer_limit', 'starts_at', 'expires_at', 'branch_id'] as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->exists('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Coupon $coupon */
        $coupon = $this->route('coupon');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'alpha_dash:ascii',
                Rule::unique('coupons', 'code')->ignore($coupon->id),
            ],
            'discount_type' => ['sometimes', 'required', Rule::enum(CouponDiscountType::class)],
            'discount_value' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'maximum_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Coupon $coupon */
            $coupon = $this->route('coupon');
            $type = $this->input('discount_type', $coupon->discount_type?->value);
            $value = $this->input('discount_value', $coupon->discount_value);

            if ($type === CouponDiscountType::Percentage->value && is_numeric($value) && (float) $value > 100) {
                $validator->errors()->add('discount_value', 'Percentage discounts cannot exceed 100.');
            }

            if (
                $type === CouponDiscountType::Fixed->value
                && $this->filled('maximum_discount_amount')
            ) {
                $validator->errors()->add('maximum_discount_amount', 'Maximum discount applies only to percentage coupons.');
            }

            if (
                $this->filled('usage_limit')
                && (int) $this->input('usage_limit') < (int) $coupon->usage_count
            ) {
                $validator->errors()->add('usage_limit', 'Usage limit cannot be lower than current usage count.');
            }
        });
    }
}
