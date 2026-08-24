<?php

namespace App\Http\Requests\Catalog;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'rating' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'review' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'review.min' => 'Please share a little more detail — at least 10 characters.',
            'review.max' => 'Your review may be at most 1000 characters.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $email = strtolower((string) $this->input('email'));
            $recentDuplicate = Review::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('created_at', '>=', now()->subHour())
                ->exists();

            if ($recentDuplicate) {
                $validator->errors()->add(
                    'email',
                    'You recently submitted a review. Please wait before submitting another.',
                );
            }
        });
    }
}
