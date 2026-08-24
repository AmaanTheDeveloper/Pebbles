<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class ModifyCustomerOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.add_on_ids' => ['sometimes', 'array'],
            'items.*.add_on_ids.*' => ['integer', 'exists:add_ons,id'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'pickup_notes' => ['nullable', 'string', 'max:1000'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
