<?php

namespace App\Actions;

use App\Models\User;

class ChangeUserPassword
{
    public function handle(User $user, string $password): User
    {
        $user->update([
            'password' => $password,
        ]);

        return $user->fresh(['branch', 'defaultAddress']);
    }
}
