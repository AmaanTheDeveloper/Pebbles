<?php

namespace App\Http\Requests\Catalog;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IndexCatalogProductRequest extends FormRequest
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
            'category' => ['sometimes', 'nullable', 'string', 'max:255'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'featured' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $categorySlug = $this->string('category')->toString();

                if ($categorySlug === '') {
                    return;
                }

                $exists = Category::query()
                    ->catalog()
                    ->where('slug', $categorySlug)
                    ->exists();

                if (! $exists) {
                    $validator->errors()->add('category', 'The selected category is invalid.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('featured') && is_string($this->input('featured'))) {
            $this->merge([
                'featured' => filter_var($this->input('featured'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
