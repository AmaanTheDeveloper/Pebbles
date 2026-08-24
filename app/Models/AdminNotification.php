<?php

namespace App\Models;

use App\Enums\AdminNotificationCategory;
use App\Enums\AdminNotificationPriority;
use App\Enums\AdminNotificationType;
use Database\Factories\AdminNotificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminNotification extends Model
{
    /** @use HasFactory<AdminNotificationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'recipient_user_id',
        'type',
        'category',
        'title',
        'message',
        'icon',
        'priority',
        'branch_id',
        'related_type',
        'related_id',
        'data',
        'action_url',
        'dedupe_key',
        'read_at',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AdminNotificationType::class,
            'category' => AdminNotificationCategory::class,
            'priority' => AdminNotificationPriority::class,
            'data' => 'array',
            'read_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function markAsRead(): void
    {
        if ($this->isRead()) {
            return;
        }

        $this->forceFill(['read_at' => now()])->save();
    }

    public function markAsUnread(): void
    {
        if ($this->isUnread()) {
            return;
        }

        $this->forceFill(['read_at' => null])->save();
    }

    public function archive(): void
    {
        if ($this->isArchived()) {
            return;
        }

        $this->forceFill(['archived_at' => now()])->save();
    }

    public function unarchive(): void
    {
        if (! $this->isArchived()) {
            return;
        }

        $this->forceFill(['archived_at' => null])->save();
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeForRecipient(Builder $query, User $user): Builder
    {
        return $query->where('recipient_user_id', $user->id);
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $query->where('recipient_user_id', $user->id);

        if ($user->isManager()) {
            $branchIds = $user->managedBranchIds();

            $query->where(function (Builder $builder) use ($branchIds): void {
                $builder->whereNull('branch_id');

                if ($branchIds !== []) {
                    $builder->orWhereIn('branch_id', $branchIds);
                }
            });
        }

        return $query;
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Unread notifications that count toward the navbar badge.
     *
     * @param  Builder<AdminNotification>  $query
     * @return Builder<AdminNotification>
     */
    public function scopeBadgeCountable(Builder $query): Builder
    {
        return $query->active()->unread();
    }
}
