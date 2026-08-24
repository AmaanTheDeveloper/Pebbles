<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
        $isDelivery = $this->input('type') === OrderType::Delivery->value;
        $hasAddress = $this->filled('address_id');

        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'type' => ['required', Rule::enum(OrderType::class)],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'delivery_zone_id' => [$isDelivery ? 'required' : 'nullable', 'integer', 'exists:delivery_zones,id'],
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'delivery_recipient_name' => [$isDelivery && ! $hasAddress ? 'required' : 'nullable', 'string', 'max:255'],
            'delivery_phone' => [$isDelivery && ! $hasAddress ? 'required' : 'nullable', 'string', 'max:30'],
            'delivery_line1' => [$isDelivery && ! $hasAddress ? 'required' : 'nullable', 'string', 'max:255'],
            'delivery_line2' => ['nullable', 'string', 'max:255'],
            'delivery_area' => ['nullable', 'string', 'max:255'],
            'delivery_city' => [$isDelivery && ! $hasAddress ? 'required' : 'nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'table_number' => [$this->input('type') === OrderType::DineIn->value ? 'required' : 'nullable', 'string', 'max:50'],
            'pickup_notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['sometimes', Rule::enum(PaymentMethod::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->offsetUnset('price');
        $this->offsetUnset('unit_price');
        $this->offsetUnset('subtotal');
        $this->offsetUnset('delivery_fee');
        $this->offsetUnset('total');
        $this->offsetUnset('grand_total');
        $this->offsetUnset('items');
        $this->offsetUnset('tax_amount');
        $this->offsetUnset('discount_amount');
    }
}
