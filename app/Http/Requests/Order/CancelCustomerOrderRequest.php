<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelCustomerOrderRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:1000'],
            'cancellation_reason' => ['nullable', 'string', Rule::in([
                'ordered_by_mistake',
                'need_to_change_something',
                'changed_my_mind',
                'other',
            ])],
        ];
    }

    public function resolvedReason(): ?string
    {
        $custom = trim((string) $this->input('reason', ''));

        if ($custom !== '') {
            return $custom;
        }

        return match ($this->input('cancellation_reason')) {
            'ordered_by_mistake' => 'Ordered by mistake',
            'need_to_change_something' => 'Need to change something',
            'changed_my_mind' => 'Changed my mind',
            'other' => 'Other',
            default => null,
        };
    }
}
