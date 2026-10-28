<?php

namespace App\Actions;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUserProfile
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $user, array $validated): User
    {
        return DB::transaction(function () use ($user, $validated): User {
            $updates = [];

            if (array_key_exists('name', $validated)) {
                $updates['name'] = $validated['name'];
            }

            if (array_key_exists('phone', $validated)) {
                $updates['phone'] = filled($validated['phone']) ? $validated['phone'] : null;
            }

            if (array_key_exists('email', $validated) && $validated['email'] !== $user->email) {
                $updates['email'] = $validated['email'];
                $updates['email_verified_at'] = null;
            }

            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }

            if (array_key_exists('address', $validated) && is_array($validated['address'])) {
                $this->syncDefaultAddress($user->fresh(), $validated['address']);
            }

            return $user->fresh(['branch', 'defaultAddress']);
        });
    }

    /**
     * @param  array<string, mixed>  $addressData
     */
    private function syncDefaultAddress(User $user, array $addressData): void
    {
        $fields = [
            'recipient_name',
            'phone',
            'line1',
            'line2',
            'area',
            'city',
            'instructions',
        ];

        $payload = [];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $addressData)) {
                continue;
            }

            $value = $addressData[$field];
            $payload[$field] = filled($value) ? $value : null;
        }

        if ($payload === []) {
            return;
        }

        $hasStreetAddress = filled($payload['line1'] ?? null);

        if (! $hasStreetAddress) {
            $hasAnyAddressField = collect($payload)
                ->only(['line1', 'line2', 'area', 'city', 'recipient_name', 'phone', 'instructions'])
                ->contains(fn ($value): bool => filled($value));

            // Explicit clear (all empty) removes the saved default address.
            // Incomplete payloads without line1 (e.g. city-only UI defaults) are ignored.
            if (! $hasAnyAddressField) {
                Address::query()
                    ->where('user_id', $user->id)
                    ->where('is_default', true)
                    ->delete();
            }

            return;
        }

        $payload['recipient_name'] = filled($payload['recipient_name'] ?? null)
            ? $payload['recipient_name']
            : $user->name;

        $resolvedPhone = filled($payload['phone'] ?? null)
            ? $payload['phone']
            : $user->phone;

        if (! filled($resolvedPhone)) {
            throw ValidationException::withMessages([
                'address.phone' => 'A phone number is required to save a delivery address.',
                'phone' => 'A phone number is required to save a delivery address.',
            ]);
        }

        if (! filled($payload['area'] ?? null)) {
            throw ValidationException::withMessages([
                'address.area' => 'Area / locality is required to save a delivery address.',
            ]);
        }

        if (! filled($payload['city'] ?? null)) {
            throw ValidationException::withMessages([
                'address.city' => 'City is required to save a delivery address.',
            ]);
        }

        $payload['phone'] = $resolvedPhone;
        $payload['is_default'] = true;

        Address::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'is_default' => true,
            ],
            $payload,
        );
    }
}
