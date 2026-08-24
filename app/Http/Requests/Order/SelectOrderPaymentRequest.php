<?php

namespace App\Http\Requests\Order;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectOrderPaymentRequest extends FormRequest
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
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
        ];
    }
}
