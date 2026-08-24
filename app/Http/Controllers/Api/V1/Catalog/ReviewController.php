<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Enums\ReviewStatus;
use App\Events\ReviewSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreReviewRequest;
use App\Http\Resources\Catalog\ReviewResource;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sort = $request->string('sort')->toString();
        $rating = $request->integer('rating');
        $featuredOnly = $request->boolean('featured');

        $reviews = Review::query()
            ->approved()
            ->when($featuredOnly, fn ($query) => $query->featured())
            ->when($rating >= 1 && $rating <= 5, fn ($query) => $query->where('rating', $rating))
            ->when($sort === 'highest', fn ($query) => $query->orderByDesc('rating')->orderByDesc('created_at'))
            ->when($sort === 'lowest', fn ($query) => $query->orderBy('rating')->orderByDesc('created_at'))
            ->when(! in_array($sort, ['highest', 'lowest'], true), fn ($query) => $query->orderByDesc('is_featured')->orderByDesc('created_at'))
            ->paginate(min(max($request->integer('per_page', 12), 1), 24));

        return ReviewResource::collection($reviews);
    }

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => Review::publicSummary(),
        ]);
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $review = Review::query()->create([
            'user_id' => $request->user()?->id,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'rating' => $request->validated('rating'),
            'review' => $request->validated('review'),
            'status' => ReviewStatus::Pending,
            'is_featured' => false,
            'ip_address' => $request->ip(),
        ]);

        ReviewSubmitted::dispatch($review);

        return response()->json([
            'data' => (new ReviewResource($review))->resolve(),
            'message' => 'Thank you! Your review has been submitted and is awaiting approval.',
        ], 201);
    }
}
