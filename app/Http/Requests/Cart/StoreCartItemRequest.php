<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'add_on_ids' => ['sometimes', 'array'],
            'add_on_ids.*' => ['integer', 'exists:add_ons,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Ignore any client-supplied pricing fields.
        $this->offsetUnset('price');
        $this->offsetUnset('unit_price');
        $this->offsetUnset('subtotal');
        $this->offsetUnset('total');
        $this->offsetUnset('line_total');
    }
}
