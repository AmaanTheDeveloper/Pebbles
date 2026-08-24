<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\AdminNotificationCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminNotificationResource;
use App\Models\AdminNotification;
use App\Support\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    public function __construct(private AdminNotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AdminNotification::class);

        $user = $request->user();
        $status = $request->string('status')->toString();
        $category = $request->string('category')->toString();
        $search = trim($request->string('search')->toString());
        $perPage = min(max($request->integer('per_page', 20), 1), 50);

        $query = AdminNotification::query()
            ->visibleTo($user)
            ->with('branch')
            ->when($status === 'unread', fn ($builder) => $builder->active()->unread())
            ->when($status === 'read', fn ($builder) => $builder->active()->read())
            ->when($status === 'archived', fn ($builder) => $builder->archived())
            ->when($status === '' || $status === 'all', fn ($builder) => $builder->active())
            ->when($category !== '', fn ($builder) => $builder->where('category', $category))
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($inner) use ($search): void {
                    $inner
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%')
                        ->orWhere('data->order_number', 'like', '%'.$search.'%')
                        ->orWhere('data->customer_name', 'like', '%'.$search.'%')
                        ->orWhere('data->branch_name', 'like', '%'.$search.'%')
                        ->orWhere('data->contact_name', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('created_at');

        $notifications = $query->paginate($perPage);

        return response()->json([
            'data' => AdminNotificationResource::collection($notifications)->resolve(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
            'links' => [
                'first' => $notifications->url(1),
                'last' => $notifications->url($notifications->lastPage()),
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
            'stats' => [
                'unread' => AdminNotification::query()->visibleTo($user)->badgeCountable()->count(),
                'read' => AdminNotification::query()->visibleTo($user)->active()->read()->count(),
                'archived' => AdminNotification::query()->visibleTo($user)->archived()->count(),
                'active' => AdminNotification::query()->visibleTo($user)->active()->count(),
            ],
            'categories' => collect(AdminNotificationCategory::cases())
                ->map(fn (AdminNotificationCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                ])
                ->values(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AdminNotification::class);

        return response()->json([
            'data' => [
                'unread_count' => $this->notifications->unreadCountFor($request->user()),
            ],
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AdminNotification::class);

        $user = $request->user();
        $limit = min(max($request->integer('limit', 8), 1), 15);

        $notifications = AdminNotification::query()
            ->visibleTo($user)
            ->active()
            ->with('branch')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => AdminNotificationResource::collection($notifications)->resolve(),
            'meta' => [
                'unread_count' => $this->notifications->unreadCountFor($user),
            ],
        ]);
    }

    public function show(Request $request, AdminNotification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        $notification->loadMissing('branch');

        return response()->json([
            'data' => (new AdminNotificationResource($notification))->resolve(),
        ]);
    }

    public function markRead(Request $request, AdminNotification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();

        return response()->json([
            'data' => (new AdminNotificationResource($notification->fresh('branch')))->resolve(),
            'meta' => [
                'unread_count' => $this->notifications->unreadCountFor($request->user()),
            ],
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markUnread(Request $request, AdminNotification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification->markAsUnread();

        return response()->json([
            'data' => (new AdminNotificationResource($notification->fresh('branch')))->resolve(),
            'meta' => [
                'unread_count' => $this->notifications->unreadCountFor($request->user()),
            ],
            'message' => 'Notification marked as unread.',
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AdminNotification::class);

        $updated = $this->notifications->markAllReadFor($request->user());

        return response()->json([
            'data' => [
                'updated' => $updated,
                'unread_count' => 0,
            ],
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function archive(Request $request, AdminNotification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification->archive();

        return response()->json([
            'data' => (new AdminNotificationResource($notification->fresh('branch')))->resolve(),
            'meta' => [
                'unread_count' => $this->notifications->unreadCountFor($request->user()),
            ],
            'message' => 'Notification archived.',
        ]);
    }

    public function unarchive(Request $request, AdminNotification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification->unarchive();

        return response()->json([
            'data' => (new AdminNotificationResource($notification->fresh('branch')))->resolve(),
            'meta' => [
                'unread_count' => $this->notifications->unreadCountFor($request->user()),
            ],
            'message' => 'Notification restored.',
        ]);
    }

    public function destroy(Request $request, AdminNotification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json([
            'meta' => [
                'unread_count' => $this->notifications->unreadCountFor($request->user()),
            ],
            'message' => 'Notification deleted.',
        ]);
    }

    public function bulk(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AdminNotification::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['read', 'unread', 'archive', 'unarchive', 'delete'])],
        ]);

        $notifications = AdminNotification::query()
            ->visibleTo($request->user())
            ->whereIn('id', $validated['ids'])
            ->get();

        foreach ($notifications as $notification) {
            match ($validated['action']) {
                'read' => $notification->markAsRead(),
                'unread' => $notification->markAsUnread(),
                'archive' => $notification->archive(),
                'unarchive' => $notification->unarchive(),
                'delete' => $notification->delete(),
            };
        }

        return response()->json([
            'data' => [
                'processed' => $notifications->count(),
                'unread_count' => $this->notifications->unreadCountFor($request->user()),
            ],
            'message' => 'Notifications updated.',
        ]);
    }
}
