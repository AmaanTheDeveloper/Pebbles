<?php

namespace App\Http\Resources;

use App\Models\AdminNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AdminNotification */
class AdminNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'category' => $this->category?->value,
            'category_label' => $this->category?->label(),
            'title' => $this->title,
            'message' => $this->message,
            'icon' => $this->icon,
            'priority' => $this->priority?->value,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->whenLoaded('branch', fn () => $this->branch?->name),
            'related_type' => $this->related_type,
            'related_id' => $this->related_id,
            'data' => $this->data,
            'action_url' => $this->action_url,
            'is_read' => $this->isRead(),
            'is_archived' => $this->isArchived(),
            'read_at' => $this->read_at?->toIso8601String(),
            'archived_at' => $this->archived_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
