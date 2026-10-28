<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }

        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->input('name')),
            ]);
        }
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $address = $this->input('address');

            if (! is_array($address) || ! filled($address['line1'] ?? null)) {
                return;
            }

            $phone = filled($address['phone'] ?? null)
                ? $address['phone']
                : (filled($this->input('phone')) ? $this->input('phone') : $this->user()?->phone);

            if (! filled($phone)) {
                $validator->errors()->add(
                    'address.phone',
                    'A phone number is required to save a delivery address.',
                );
            }

            if (! filled($address['area'] ?? null)) {
                $validator->errors()->add(
                    'address.area',
                    'Area / locality is required to save a delivery address.',
                );
            }

            if (! filled($address['city'] ?? null)) {
                $validator->errors()->add(
                    'address.city',
                    'City is required to save a delivery address.',
                );
            }
        });
    }
}
