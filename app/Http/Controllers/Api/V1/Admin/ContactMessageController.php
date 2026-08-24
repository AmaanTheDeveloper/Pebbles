<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ContactMessageStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactMessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $messages = ContactMessage::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('subject', 'like', '%'.$search.'%')
                        ->orWhere('message', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $messages->getCollection()->map(fn (ContactMessage $message): array => $this->transform($message))->values(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'from' => $messages->firstItem(),
                'to' => $messages->lastItem(),
            ],
            'links' => [
                'first' => $messages->url(1),
                'last' => $messages->url($messages->lastPage()),
                'prev' => $messages->previousPageUrl(),
                'next' => $messages->nextPageUrl(),
            ],
            'stats' => [
                'new' => ContactMessage::query()->where('status', ContactMessageStatus::New)->count(),
                'read' => ContactMessage::query()->where('status', ContactMessageStatus::Read)->count(),
                'replied' => ContactMessage::query()->where('status', ContactMessageStatus::Replied)->count(),
                'archived' => ContactMessage::query()->where('status', ContactMessageStatus::Archived)->count(),
            ],
        ]);
    }

    public function show(ContactMessage $contactMessage): JsonResponse
    {
        return response()->json([
            'data' => $this->transform($contactMessage),
        ]);
    }

    public function update(Request $request, ContactMessage $contactMessage): JsonResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ContactMessageStatus::class)],
        ]);

        $contactMessage->update($validated);

        return response()->json([
            'data' => $this->transform($contactMessage->fresh()),
            'message' => 'Contact message updated successfully.',
        ]);
    }

    public function destroy(ContactMessage $contactMessage): JsonResponse
    {
        $contactMessage->delete();

        return response()->json([
            'message' => 'Contact message deleted successfully.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(ContactMessage $message): array
    {
        return [
            'id' => $message->id,
            'name' => $message->name,
            'email' => $message->email,
            'phone' => $message->phone,
            'subject' => $message->subject,
            'message' => $message->message,
            'status' => $message->status->value,
            'status_label' => $message->status->label(),
            'ip_address' => $message->ip_address,
            'created_at' => $message->created_at?->toIso8601String(),
            'updated_at' => $message->updated_at?->toIso8601String(),
        ];
    }
}
