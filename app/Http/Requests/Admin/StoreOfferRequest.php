<?php

namespace App\Http\Requests\Admin;

use App\Actions\ProcessAndStoreProductImage;
use App\Enums\OfferScope;
use App\Enums\OfferType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'short_description',
            'description',
            'offer_value',
            'cta_label',
            'cta_url',
            'starts_at',
            'ends_at',
            'maximum_discount_amount',
        ];

        foreach ($nullable as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->exists('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }

        if ($this->exists('sort_order') && $this->input('sort_order') !== null && $this->input('sort_order') !== '') {
            $this->merge(['sort_order' => (int) $this->input('sort_order')]);
        }

        if (! $this->exists('scope') || $this->input('scope') === '' || $this->input('scope') === null) {
            $this->merge(['scope' => OfferScope::EntireStore->value]);
        }

        $this->merge([
            'category_ids' => $this->normalizeIdList($this->input('category_ids')),
            'product_ids' => $this->normalizeIdList($this->input('product_ids')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(OfferType::class)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.ProcessAndStoreProductImage::MAX_UPLOAD_KB,
            ],
            'offer_value' => ['nullable', 'string', 'max:255'],
            'scope' => ['required', Rule::enum(OfferScope::class)],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'maximum_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $scope = $this->input('scope');

            if ($scope === OfferScope::Categories->value && blank($this->input('category_ids'))) {
                $validator->errors()->add('category_ids', 'Select at least one category for this offer.');
            }

            if ($scope === OfferScope::Products->value && blank($this->input('product_ids'))) {
                $validator->errors()->add('product_ids', 'Select at least one product for this offer.');
            }
        });
    }

    /**
     * @return list<int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        return array_values(array_unique(array_map('intval', $value)));
    }
}
