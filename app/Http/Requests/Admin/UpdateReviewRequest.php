<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_featured')) {
            $this->merge([
                'is_featured' => filter_var($this->input('is_featured'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
            'rating' => ['sometimes', 'required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'review' => ['sometimes', 'required', 'string', 'min:10', 'max:1000'],
            'status' => ['sometimes', 'required', Rule::enum(ReviewStatus::class)],
            'is_featured' => ['sometimes', 'boolean'],
        ];
    }
}
