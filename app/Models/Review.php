<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'rating',
        'review',
        'status',
        'is_featured',
        'ip_address',
    ];

    protected $attributes = [
        'status' => ReviewStatus::Pending->value,
        'is_featured' => false,
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'is_featured' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Review>  $query
     * @return Builder<Review>
     */
    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('status', ReviewStatus::Approved);
    }

    /**
     * @param  Builder<Review>  $query
     * @return Builder<Review>
     */
    #[Scope]
    protected function featured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * @return array{1: int, 2: int, 3: int, 4: int, 5: int}
     */
    public static function ratingDistributionForApproved(): array
    {
        $counts = self::query()
            ->approved()
            ->selectRaw('rating, COUNT(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        return [
            1 => (int) ($counts[1] ?? 0),
            2 => (int) ($counts[2] ?? 0),
            3 => (int) ($counts[3] ?? 0),
            4 => (int) ($counts[4] ?? 0),
            5 => (int) ($counts[5] ?? 0),
        ];
    }

    /**
     * @return array{
     *     average_rating: float|null,
     *     total_reviews: int,
     *     distribution: array{1: int, 2: int, 3: int, 4: int, 5: int}
     * }
     */
    public static function publicSummary(): array
    {
        $approvedQuery = self::query()->approved();
        $totalReviews = (clone $approvedQuery)->count();
        $averageRating = $totalReviews > 0
            ? round((float) (clone $approvedQuery)->avg('rating'), 1)
            : null;

        return [
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
            'distribution' => self::ratingDistributionForApproved(),
        ];
    }

    /**
     * @return array{
     *     total: int,
     *     pending: int,
     *     approved: int,
     *     rejected: int,
     *     average_rating: float|null
     * }
     */
    public static function adminStats(): array
    {
        $counts = self::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $approvedCount = (int) ($counts[ReviewStatus::Approved->value] ?? 0);

        return [
            'total' => self::query()->count(),
            'pending' => (int) ($counts[ReviewStatus::Pending->value] ?? 0),
            'approved' => $approvedCount,
            'rejected' => (int) ($counts[ReviewStatus::Rejected->value] ?? 0),
            'average_rating' => $approvedCount > 0
                ? round((float) self::query()->approved()->avg('rating'), 1)
                : null,
        ];
    }
}
