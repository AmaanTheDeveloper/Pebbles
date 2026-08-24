<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\User;
use App\Support\CustomerOrderLifecycle;
use App\Support\ProfileSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeleteUserAccount
{
    public function handle(User $user): void
    {
        if ($this->hasActiveOrders($user)) {
            throw ValidationException::withMessages([
                'password' => 'You cannot delete your account while you have an active order in progress.',
            ]);
        }

        DB::transaction(function () use ($user): void {
            ProfileSettings::removePhoto($user);

            $user->forceFill([
                'name' => 'Deleted User',
                'email' => 'deleted+'.$user->id.'+'.Str::lower(Str::random(8)).'@deleted.pebbles.local',
                'phone' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
            ])->save();

            $user->addresses()->delete();
            $user->delete();
        });
    }

    private function hasActiveOrders(User $user): bool
    {
        if (! $user->isUser()) {
            throw ValidationException::withMessages([
                'password' => 'This account type cannot be deleted from the profile page.',
            ]);
        }

        $finalStatuses = array_map(
            fn ($status) => $status->value,
            CustomerOrderLifecycle::finalStatuses(),
        );

        return Order::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', $finalStatuses)
            ->exists();
    }
}
