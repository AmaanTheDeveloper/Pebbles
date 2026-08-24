<?php

namespace App\Listeners;

use App\Enums\AdminNotificationType;
use App\Events\ContactMessageSubmitted;
use App\Support\AdminNotificationService;

class CreateContactMessageAdminNotification
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function handle(ContactMessageSubmitted $event): void
    {
        $message = $event->contactMessage;
        $name = $message->name ?: 'Someone';
        $subject = trim((string) ($message->subject ?? ''));

        $body = "{$name} submitted a new contact message.";

        if ($subject !== '') {
            $body .= " Subject: {$subject}.";
        }

        $this->notifications->notifyAdmins(
            type: AdminNotificationType::ContactMessageSubmitted,
            title: 'New Contact Message',
            message: $body,
            related: $message,
            branchId: null,
            actionUrl: '/admin/contact-messages',
            dedupeKey: "contact.message_submitted:{$message->id}",
            data: [
                'contact_name' => $name,
                'contact_email' => $message->email,
                'subject' => $subject !== '' ? $subject : null,
            ],
        );
    }
}
