<?php

namespace App\Listeners;

use App\Enums\AdminNotificationType;
use App\Enums\UserRole;
use App\Support\AdminNotificationService;
use Illuminate\Auth\Events\Registered;

class CreateCustomerRegisteredAdminNotification
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function handle(Registered $event): void
    {
        $user = $event->user;

        if ($user->role !== UserRole::User) {
            return;
        }

        $this->notifications->notifyAdmins(
            type: AdminNotificationType::CustomerRegistered,
            title: 'New Customer',
            message: "{$user->name} has registered a new customer account.",
            related: $user,
            branchId: null,
            actionUrl: null,
            dedupeKey: "customer.registered:{$user->id}",
            data: [
                'customer_name' => $user->name,
                'customer_email' => $user->email,
            ],
        );
    }
}
