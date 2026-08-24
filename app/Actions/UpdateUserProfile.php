<?php

namespace App\Actions;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
                $this->syncDefaultAddress($user, $validated['address']);
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

        $hasMeaningfulData = collect($payload)
            ->only(['line1', 'area', 'city', 'recipient_name', 'phone'])
            ->filter(fn (?string $value): bool => filled($value))
            ->isNotEmpty();

        if (! $hasMeaningfulData) {
            Address::query()
                ->where('user_id', $user->id)
                ->where('is_default', true)
                ->delete();

            return;
        }

        $payload['recipient_name'] ??= $user->name;
        $payload['phone'] ??= $user->phone;
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
