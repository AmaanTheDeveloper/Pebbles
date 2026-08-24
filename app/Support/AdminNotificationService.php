<?php

namespace App\Support;

use App\Enums\AdminNotificationPriority;
use App\Enums\AdminNotificationType;
use App\Enums\UserRole;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class AdminNotificationService
{
    /**
     * Create one notification per eligible admin recipient.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, AdminNotification>
     */
    public function notifyAdmins(
        AdminNotificationType $type,
        string $title,
        string $message,
        ?Model $related = null,
        ?int $branchId = null,
        ?string $actionUrl = null,
        ?string $dedupeKey = null,
        array $data = [],
        ?AdminNotificationPriority $priority = null,
    ): Collection {
        $recipients = $this->resolveRecipients($branchId);

        if ($recipients->isEmpty()) {
            return collect();
        }

        $priority ??= $type->defaultPriority();
        $created = collect();

        foreach ($recipients as $recipient) {
            $notification = $this->createForRecipient(
                recipient: $recipient,
                type: $type,
                title: $title,
                message: $message,
                related: $related,
                branchId: $branchId,
                actionUrl: $actionUrl,
                dedupeKey: $dedupeKey,
                data: $data,
                priority: $priority,
            );

            if ($notification !== null) {
                $created->push($notification);
            }
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForRecipient(
        User $recipient,
        AdminNotificationType $type,
        string $title,
        string $message,
        ?Model $related = null,
        ?int $branchId = null,
        ?string $actionUrl = null,
        ?string $dedupeKey = null,
        array $data = [],
        ?AdminNotificationPriority $priority = null,
    ): ?AdminNotification {
        if ($dedupeKey !== null) {
            $existing = AdminNotification::query()
                ->where('recipient_user_id', $recipient->id)
                ->where('dedupe_key', $dedupeKey)
                ->first();

            if ($existing !== null) {
                return null;
            }
        }

        try {
            return AdminNotification::query()->create([
                'recipient_user_id' => $recipient->id,
                'type' => $type,
                'category' => $type->category(),
                'title' => $title,
                'message' => $message,
                'icon' => $type->icon(),
                'priority' => $priority ?? $type->defaultPriority(),
                'branch_id' => $branchId,
                'related_type' => $related?->getMorphClass(),
                'related_id' => $related?->getKey(),
                'data' => $data === [] ? null : $data,
                'action_url' => $actionUrl,
                'dedupe_key' => $dedupeKey,
            ]);
        } catch (QueryException $exception) {
            if ($dedupeKey !== null && $this->isUniqueConstraintViolation($exception)) {
                return null;
            }

            throw $exception;
        }
    }

    protected function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || (string) $exception->getCode() === '23000';
    }

    /**
     * Super Admins always receive notifications.
     * Managers receive notifications for their assigned branch (and global events).
     *
     * @return Collection<int, User>
     */
    public function resolveRecipients(?int $branchId = null): Collection
    {
        $superAdmins = User::query()
            ->where('role', UserRole::SuperAdmin)
            ->get();

        $branchManagers = $this->resolveBranchManagerRecipients($branchId);

        return $superAdmins
            ->concat($branchManagers)
            ->unique('id')
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    protected function resolveBranchManagerRecipients(?int $branchId): Collection
    {
        if ($branchId === null) {
            return collect();
        }

        return User::query()
            ->where('role', UserRole::Manager)
            ->where('branch_id', $branchId)
            ->get();
    }

    public function unreadCountFor(User $user): int
    {
        return AdminNotification::query()
            ->visibleTo($user)
            ->badgeCountable()
            ->count();
    }

    public function markAllReadFor(User $user): int
    {
        return AdminNotification::query()
            ->visibleTo($user)
            ->active()
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function formatMoney(float|string|null $amount): string
    {
        return 'Rs. '.number_format((float) ($amount ?? 0), 0);
    }
}
