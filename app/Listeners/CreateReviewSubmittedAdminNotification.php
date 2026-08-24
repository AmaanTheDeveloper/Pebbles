<?php

namespace App\Listeners;

use App\Enums\AdminNotificationType;
use App\Events\ReviewSubmitted;
use App\Support\AdminNotificationService;

class CreateReviewSubmittedAdminNotification
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function handle(ReviewSubmitted $event): void
    {
        $review = $event->review;
        $name = $review->name ?: 'A customer';
        $rating = (int) $review->rating;

        $this->notifications->notifyAdmins(
            type: AdminNotificationType::ReviewSubmitted,
            title: 'New Review',
            message: "{$name} submitted a {$rating}-star review awaiting moderation.",
            related: $review,
            branchId: null,
            actionUrl: '/admin/reviews',
            dedupeKey: "review.submitted:{$review->id}",
            data: [
                'reviewer_name' => $name,
                'rating' => $rating,
            ],
        );
    }
}
