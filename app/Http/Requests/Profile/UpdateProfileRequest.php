<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $user = $this->user();

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'address' => ['sometimes', 'nullable', 'array'],
            'address.recipient_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'address.line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.area' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.instructions' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
