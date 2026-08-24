<?php

namespace App\Http\Requests\Catalog;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IndexCatalogDeliveryZoneRequest extends FormRequest
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
            'branch' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $branchSlug = $this->string('branch')->toString();

                if ($branchSlug === '') {
                    return;
                }

                $exists = Branch::query()
                    ->catalog()
                    ->where('slug', $branchSlug)
                    ->exists();

                if (! $exists) {
                    $validator->errors()->add('branch', 'The selected branch is invalid.');
                }
            },
        ];
    }
}
