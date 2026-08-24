<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReviewRequest;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $rating = $request->integer('rating');

        $reviews = Review::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('review', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($rating >= 1 && $rating <= 5, fn ($query) => $query->where('rating', $rating))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $reviews->getCollection()->map(fn (Review $review): array => $this->transformReview($review))->values(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'from' => $reviews->firstItem(),
                'to' => $reviews->lastItem(),
            ],
            'links' => [
                'first' => $reviews->url(1),
                'last' => $reviews->url($reviews->lastPage()),
                'prev' => $reviews->previousPageUrl(),
                'next' => $reviews->nextPageUrl(),
            ],
            'stats' => Review::adminStats(),
        ]);
    }

    public function show(Review $review): JsonResponse
    {
        return response()->json([
            'data' => $this->transformReview($review),
        ]);
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $review->update($request->validated());

        return response()->json([
            'data' => $this->transformReview($review->fresh()),
            'message' => match ($review->status->value) {
                'approved' => 'Review approved successfully.',
                'rejected' => 'Review rejected successfully.',
                default => 'Review updated successfully.',
            },
        ]);
    }

    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully.',
        ], 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'user_id' => $review->user_id,
            'name' => $review->name,
            'email' => $review->email,
            'rating' => $review->rating,
            'review' => $review->review,
            'status' => $review->status->value,
            'status_label' => $review->status->label(),
            'is_featured' => $review->is_featured,
            'ip_address' => $review->ip_address,
            'created_at' => $review->created_at?->toIso8601String(),
            'updated_at' => $review->updated_at?->toIso8601String(),
        ];
    }
}
