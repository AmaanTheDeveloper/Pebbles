<?php

namespace App\Http\Requests\Admin;

use App\Actions\ProcessAndStoreProductImage;
use App\Enums\OfferScope;
use App\Enums\OfferType;
use App\Models\Offer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOfferRequest extends FormRequest
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

        if ($this->exists('category_ids')) {
            $this->merge(['category_ids' => $this->normalizeIdList($this->input('category_ids'))]);
        }

        if ($this->exists('product_ids')) {
            $this->merge(['product_ids' => $this->normalizeIdList($this->input('product_ids'))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', Rule::enum(OfferType::class)],
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
            'scope' => ['sometimes', Rule::enum(OfferScope::class)],
            'category_ids' => ['sometimes', 'nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'product_ids' => ['sometimes', 'nullable', 'array'],
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
            /** @var Offer|null $offer */
            $offer = $this->route('offer');
            $scope = $this->input('scope', $offer?->scope?->value);

            if ($scope === OfferScope::Categories->value) {
                $categoryIds = $this->exists('category_ids')
                    ? $this->input('category_ids')
                    : $offer?->categories()->pluck('categories.id')->all();

                if (blank($categoryIds)) {
                    $validator->errors()->add('category_ids', 'Select at least one category for this offer.');
                }
            }

            if ($scope === OfferScope::Products->value) {
                $productIds = $this->exists('product_ids')
                    ? $this->input('product_ids')
                    : $offer?->products()->pluck('products.id')->all();

                if (blank($productIds)) {
                    $validator->errors()->add('product_ids', 'Select at least one product for this offer.');
                }
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
