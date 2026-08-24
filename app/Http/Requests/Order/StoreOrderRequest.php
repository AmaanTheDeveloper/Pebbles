<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'type' => ['required', Rule::enum(OrderType::class)],
            'customer_notes' => ['nullable', 'string'],
            'delivery_zone_id' => ['required_if:type,delivery', 'nullable', 'exists:delivery_zones,id'],
            'address_id' => ['required_if:type,delivery', 'nullable', 'exists:addresses,id'],
            'table_number' => ['required_if:type,dine_in', 'nullable', 'string', 'max:50'],
            'pickup_notes' => ['nullable', 'string'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.add_on_ids' => ['sometimes', 'array'],
            'items.*.add_on_ids.*' => ['integer', 'exists:add_ons,id'],
        ];
    }
}
