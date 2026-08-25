<?php

namespace App\Http\Requests\Branch;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:branches,slug'],
            'code' => ['nullable', 'string', 'max:50', 'unique:branches,code'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'opens_at' => ['nullable', 'date_format:H:i'],
            'closes_at' => ['nullable', 'date_format:H:i'],
            'opening_hours' => ['nullable', 'array'],
            'opening_hours.*.closed' => ['sometimes', 'boolean'],
            'opening_hours.*.opens' => ['nullable', 'date_format:H:i'],
            'opening_hours.*.closes' => ['nullable', 'date_format:H:i'],
            'opening_hours.*.note' => ['nullable', 'string', 'max:160'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'preparation_minutes' => ['sometimes', 'integer', 'min:1', 'max:240'],
            'delivery_minutes' => ['sometimes', 'integer', 'min:1', 'max:240'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('opening_hours'))) {
            $decoded = json_decode($this->input('opening_hours'), true);
            if (is_array($decoded)) {
                $this->merge(['opening_hours' => $decoded]);
            }
        }
    }
}
