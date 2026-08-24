<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->offsetUnset('price');
        $this->offsetUnset('unit_price');
        $this->offsetUnset('subtotal');
        $this->offsetUnset('total');
        $this->offsetUnset('product_id');
        $this->offsetUnset('product_variant_id');
        $this->offsetUnset('add_on_ids');
    }
}
