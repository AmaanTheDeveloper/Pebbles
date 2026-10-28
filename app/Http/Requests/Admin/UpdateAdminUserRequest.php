<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $role = UserRole::tryFrom((string) $this->input('role'));

            if ($role === UserRole::Manager) {
                if (blank($this->input('branch_id'))) {
                    $validator->errors()->add('branch_id', 'A branch must be assigned to a Manager.');

                    return;
                }

                $branchActive = Branch::query()
                    ->whereKey($this->input('branch_id'))
                    ->where('is_active', true)
                    ->exists();

                if (! $branchActive) {
                    $validator->errors()->add('branch_id', 'Managers can only be assigned to active branches.');
                }
            }

            if (in_array($role, [UserRole::User, UserRole::SuperAdmin], true) && filled($this->input('branch_id'))) {
                // Branch is cleared in the controller; ignore client-provided branch for non-managers.
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'branch_id.required' => 'A branch must be assigned to a Manager.',
        ];
    }
}
