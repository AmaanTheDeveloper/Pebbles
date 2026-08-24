<?php

namespace App\Http\Requests\Admin;

use App\Actions\ProcessAndStoreProductImage;
use App\Enums\OfferType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $nullable = ['short_description', 'description', 'offer_value', 'cta_label', 'cta_url', 'starts_at', 'ends_at'];

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
            'cta_label' => ['nullable', 'string', 'max:100'],
            'cta_url' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
