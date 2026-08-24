<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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
            'label' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'delivery_zone_id' => ['nullable', 'exists:delivery_zones,id'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
