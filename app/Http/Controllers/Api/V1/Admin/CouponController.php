<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;

class CouponController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();
        $type = $request->string('type')->toString();

        $coupons = Coupon::query()
            ->with('branch:id,name')
            ->withCount('redemptions')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('code', 'like', '%'.strtoupper(trim($search)).'%');
            })
            ->when($type !== '', fn ($query) => $query->where('discount_type', $type))
            ->when($status !== '', function ($query) use ($status): void {
                $now = now();

                match ($status) {
                    'active' => $query
                        ->where('is_active', true)
                        ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                        ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $now))
                        ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('usage_count', '<', 'usage_limit')),
                    'inactive' => $query->where('is_active', false),
                    'scheduled' => $query
                        ->where('is_active', true)
                        ->whereNotNull('starts_at')
                        ->where('starts_at', '>', $now),
                    'expired' => $query
                        ->where('is_active', true)
                        ->whereNotNull('expires_at')
                        ->where('expires_at', '<=', $now),
                    'fixed' => $query->where('discount_type', 'fixed'),
                    'percentage' => $query->where('discount_type', 'percentage'),
                    default => null,
                };
            })
            ->orderByDesc('id')
            ->paginate(20);

        $coupons->setCollection(
            $coupons->getCollection()->map(fn (Coupon $coupon): array => $this->transformCoupon($coupon))
        );

        return JsonResource::collection($coupons);
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['usage_count'] = 0;

        $coupon = Coupon::query()->create($data);

        return response()->json(['data' => $this->transformCoupon($coupon->load('branch:id,name')->loadCount('redemptions'))], 201);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        $coupon->load('branch:id,name')->loadCount('redemptions');

        return response()->json(['data' => $this->transformCoupon($coupon)]);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $coupon->update($request->validated());

        return response()->json([
            'data' => $this->transformCoupon($coupon->fresh(['branch:id,name'])->loadCount('redemptions')),
        ]);
    }

    public function destroy(Coupon $coupon): Response
    {
        if ($coupon->redemptions()->exists() || $coupon->usage_count > 0) {
            $coupon->update(['is_active' => false]);
            $coupon->delete();
        } else {
            $coupon->forceDelete();
        }

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCoupon(Coupon $coupon): array
    {
        $usageLabel = $coupon->usage_limit === null
            ? $coupon->usage_count.' uses'
            : $coupon->usage_count.' / '.$coupon->usage_limit;

        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type?->value,
            'discount_type_label' => $coupon->discount_type?->label(),
            'discount_value' => $coupon->discount_value,
            'minimum_order_amount' => $coupon->minimum_order_amount,
            'maximum_discount_amount' => $coupon->maximum_discount_amount,
            'usage_limit' => $coupon->usage_limit,
            'usage_count' => $coupon->usage_count,
            'usage_label' => $usageLabel,
            'per_customer_limit' => $coupon->per_customer_limit,
            'starts_at' => $coupon->starts_at,
            'expires_at' => $coupon->expires_at,
            'is_active' => $coupon->is_active,
            'status' => $coupon->statusLabel(),
            'branch_id' => $coupon->branch_id,
            'branch' => $coupon->relationLoaded('branch') && $coupon->branch !== null
                ? ['id' => $coupon->branch->id, 'name' => $coupon->branch->name]
                : null,
            'created_by' => $coupon->created_by,
            'created_at' => $coupon->created_at,
            'updated_at' => $coupon->updated_at,
        ];
    }
}
