<?php

namespace App\Policies;

use App\Models\AdminNotification;
use App\Models\User;

class AdminNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAdmin();
    }

    public function view(User $user, AdminNotification $adminNotification): bool
    {
        return $this->owns($user, $adminNotification);
    }

    public function update(User $user, AdminNotification $adminNotification): bool
    {
        return $this->owns($user, $adminNotification);
    }

    public function delete(User $user, AdminNotification $adminNotification): bool
    {
        return $this->owns($user, $adminNotification);
    }

    private function owns(User $user, AdminNotification $adminNotification): bool
    {
        if (! $user->canAccessAdmin()) {
            return false;
        }

        return (int) $adminNotification->recipient_user_id === (int) $user->id;
    }
}
