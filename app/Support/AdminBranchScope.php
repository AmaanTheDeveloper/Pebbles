<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

class AdminBranchScope
{
    /**
     * Resolve the effective branch filter for an admin request.
     * Managers are always locked to their assigned branch.
     */
    public static function resolveBranchId(User $user, mixed $requestedBranchId = null): ?int
    {
        if ($user->isManager()) {
            abort_unless($user->branch_id !== null, Response::HTTP_FORBIDDEN, 'Manager accounts must be assigned to a branch.');

            return (int) $user->branch_id;
        }

        if ($user->isSuperAdmin() && filled($requestedBranchId)) {
            return (int) $requestedBranchId;
        }

        return null;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrain(Builder $query, User $user, mixed $requestedBranchId = null, string $column = 'branch_id'): Builder
    {
        $branchId = self::resolveBranchId($user, $requestedBranchId);

        if ($branchId !== null) {
            $query->where($column, $branchId);
        }

        return $query;
    }

    /**
     * Constrain payments via their related order branch.
     *
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public static function constrainPayments(Builder $query, User $user, mixed $requestedBranchId = null): Builder
    {
        $branchId = self::resolveBranchId($user, $requestedBranchId);

        if ($branchId !== null) {
            $query->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('branch_id', $branchId));
        }

        return $query;
    }

    public static function authorizeBranchAccess(User $user, ?int $branchId): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        abort_unless(
            $user->isManager() && $user->branch_id !== null && (int) $user->branch_id === (int) $branchId,
            Response::HTTP_FORBIDDEN,
            'You are not authorized to access this branch resource.',
        );
    }

    public static function authorizeOrderAccess(User $user, ?int $branchId): void
    {
        self::authorizeBranchAccess($user, $branchId);
    }
}
