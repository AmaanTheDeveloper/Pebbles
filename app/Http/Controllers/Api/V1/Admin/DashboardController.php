<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Support\AdminBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $todayStart = Carbon::today();
        $todayEnd = Carbon::tomorrow();
        $user = $request->user()->loadMissing('branch');
        $branchId = AdminBranchScope::resolveBranchId($user, $request->integer('branch_id') ?: null);

        $ordersQuery = Order::query();
        AdminBranchScope::constrain($ordersQuery, $user, $branchId);

        $ordersToday = (clone $ordersQuery)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        $salesToday = (string) (clone $ordersQuery)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->sum('grand_total');

        $pendingOrders = (clone $ordersQuery)->where('status', OrderStatus::Pending->value)->count();
        $preparingOrders = (clone $ordersQuery)->where('status', OrderStatus::Preparing->value)->count();
        $readyOrders = (clone $ordersQuery)->where('status', OrderStatus::Ready->value)->count();

        $paymentsQuery = Payment::query();
        AdminBranchScope::constrainPayments($paymentsQuery, $user, $branchId);

        $pendingPaymentVerification = (clone $paymentsQuery)
            ->where('status', PaymentStatus::Submitted->value)
            ->count();

        $activeProducts = Product::query()
            ->where('is_active', true)
            ->where('is_available', true)
            ->count();

        return response()->json([
            'data' => [
                'orders_today' => $ordersToday,
                'pending_orders' => $pendingOrders,
                'preparing_orders' => $preparingOrders,
                'ready_orders' => $readyOrders,
                'pending_payment_verification' => $pendingPaymentVerification,
                'sales_today' => number_format((float) $salesToday, 2, '.', ''),
                'active_products' => $user->isSuperAdmin() ? $activeProducts : null,
                'branch_id' => $branchId,
                'branch_name' => $user->isManager() ? $user->branch?->name : null,
            ],
        ]);
    }
}
