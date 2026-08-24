<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderCustomerOrderRequest extends FormRequest
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
            'mode' => ['sometimes', 'string', Rule::in(['merge', 'replace'])],
        ];
    }

    public function mode(): string
    {
        return $this->input('mode', 'merge');
    }
}
