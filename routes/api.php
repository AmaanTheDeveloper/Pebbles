<?php

use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\Admin\AddOnGroupController as AdminAddOnGroupController;
use App\Http\Controllers\Api\V1\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\BranchController as AdminBranchController;
use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\DeliveryZoneController as AdminDeliveryZoneController;
use App\Http\Controllers\Api\V1\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Api\V1\Admin\HomepageController as AdminHomepageController;
use App\Http\Controllers\Api\V1\Admin\MenuBookController as AdminMenuBookController;
use App\Http\Controllers\Api\V1\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\OfferController as AdminOfferController;
use App\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\Cart\CartController;
use App\Http\Controllers\Api\V1\Cart\CartItemController;
use App\Http\Controllers\Api\V1\Catalog\BranchController as CatalogBranchController;
use App\Http\Controllers\Api\V1\Catalog\BrandingController as CatalogBrandingController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController as CatalogCategoryController;
use App\Http\Controllers\Api\V1\Catalog\ContactController as CatalogContactController;
use App\Http\Controllers\Api\V1\Catalog\DeliveryZoneController as CatalogDeliveryZoneController;
use App\Http\Controllers\Api\V1\Catalog\GalleryController as CatalogGalleryController;
use App\Http\Controllers\Api\V1\Catalog\HomepageController as CatalogHomepageController;
use App\Http\Controllers\Api\V1\Catalog\MenuBookController as CatalogMenuBookController;
use App\Http\Controllers\Api\V1\Catalog\OfferController as CatalogOfferController;
use App\Http\Controllers\Api\V1\Catalog\PopupController as CatalogPopupController;
use App\Http\Controllers\Api\V1\Catalog\ProductController as CatalogProductController;
use App\Http\Controllers\Api\V1\Catalog\ReviewController as CatalogReviewController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\CustomerOrderController;
use App\Http\Controllers\Api\V1\Internal\PaymentVerificationController;
use App\Http\Controllers\Api\V1\OrderPaymentController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/catalog')->group(function () {
    Route::get('/categories', [CatalogCategoryController::class, 'index']);
    Route::get('/products', [CatalogProductController::class, 'index']);
    Route::get('/products/{product}', [CatalogProductController::class, 'show']);
    Route::get('/branches', [CatalogBranchController::class, 'index']);
    Route::get('/branding', [CatalogBrandingController::class, 'show']);
    Route::get('/popup', [CatalogPopupController::class, 'show']);
    Route::get('/delivery-zones', [CatalogDeliveryZoneController::class, 'index']);
    Route::get('/offers', [CatalogOfferController::class, 'index']);
    Route::get('/gallery', [CatalogGalleryController::class, 'index']);
    Route::get('/homepage', [CatalogHomepageController::class, 'show']);
    Route::get('/menu-book', [CatalogMenuBookController::class, 'index']);
    Route::get('/reviews', [CatalogReviewController::class, 'index']);
    Route::get('/reviews/summary', [CatalogReviewController::class, 'summary']);
    Route::post('/reviews', [CatalogReviewController::class, 'store'])->middleware('throttle:reviews');
    Route::get('/contact', [CatalogContactController::class, 'show']);
    Route::post('/contact-messages', [CatalogContactController::class, 'store'])->middleware('throttle:contact-messages');
});

Route::get('/v1/payment-methods', [PaymentMethodController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'show']);

    Route::get('/orders/{order}', [OrderController::class, 'show']);

    Route::prefix('v1')->group(function () {
        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/photo', [ProfileController::class, 'updatePhoto']);
        Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto']);
        Route::patch('/profile/password', [ProfileController::class, 'updatePassword']);
        Route::delete('/profile/account', [ProfileController::class, 'destroy']);

        Route::get('/cart', [CartController::class, 'show']);
        Route::delete('/cart', [CartController::class, 'destroy']);
        Route::post('/cart/items', [CartItemController::class, 'store']);
        Route::patch('/cart/items/{item}', [CartItemController::class, 'update']);
        Route::delete('/cart/items/{item}', [CartItemController::class, 'destroy']);
        Route::post('/checkout', [CheckoutController::class, 'store']);

        Route::get('/orders/active-status', [CustomerOrderController::class, 'activeStatus']);
        Route::get('/orders', [CustomerOrderController::class, 'index']);
        Route::get('/orders/{order}', [CustomerOrderController::class, 'show']);
        Route::get('/orders/{order}/status-history', [CustomerOrderController::class, 'statusHistory']);
        Route::post('/orders/{order}/cancel', [CustomerOrderController::class, 'cancel']);
        Route::post('/orders/{order}/modify/preview', [CustomerOrderController::class, 'previewModify']);
        Route::post('/orders/{order}/modify', [CustomerOrderController::class, 'modify']);
        Route::post('/orders/{order}/reorder', [CustomerOrderController::class, 'reorder']);
        Route::post('/orders/{order}/payment', [OrderPaymentController::class, 'store']);
        Route::post('/orders/{order}/payment/proof', [OrderPaymentController::class, 'storeProof']);
        Route::get('/orders/{order}/payment/proof/{proof}', [OrderPaymentController::class, 'showProof']);

        Route::middleware('role:super_admin,manager')->prefix('internal')->group(function () {
            Route::post('/orders/{order}/payment/verify', [PaymentVerificationController::class, 'verify']);
            Route::post('/orders/{order}/payment/reject', [PaymentVerificationController::class, 'reject']);
        });

        Route::middleware('role:super_admin,manager')->prefix('admin')->group(function () {
            Route::get('/dashboard', AdminDashboardController::class);

            Route::get('/notifications/unread-count', [AdminNotificationController::class, 'unreadCount']);
            Route::get('/notifications/recent', [AdminNotificationController::class, 'recent']);
            Route::get('/notifications', [AdminNotificationController::class, 'index']);
            Route::post('/notifications/mark-all-read', [AdminNotificationController::class, 'markAllRead']);
            Route::post('/notifications/bulk', [AdminNotificationController::class, 'bulk']);
            Route::get('/notifications/{notification}', [AdminNotificationController::class, 'show']);
            Route::post('/notifications/{notification}/read', [AdminNotificationController::class, 'markRead']);
            Route::post('/notifications/{notification}/unread', [AdminNotificationController::class, 'markUnread']);
            Route::post('/notifications/{notification}/archive', [AdminNotificationController::class, 'archive']);
            Route::post('/notifications/{notification}/unarchive', [AdminNotificationController::class, 'unarchive']);
            Route::delete('/notifications/{notification}', [AdminNotificationController::class, 'destroy']);

            Route::get('/orders', [AdminOrderController::class, 'index']);
            Route::get('/orders/{order}', [AdminOrderController::class, 'show']);
            Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus']);

            Route::get('/payments', [AdminPaymentController::class, 'index']);
            Route::get('/payments/{payment}', [AdminPaymentController::class, 'show']);
            Route::post('/orders/{order}/payment/verify', [AdminPaymentController::class, 'verify']);
            Route::post('/orders/{order}/payment/reject', [AdminPaymentController::class, 'reject']);
            Route::get('/orders/{order}/payment/proof/{proof}', [AdminPaymentController::class, 'showProof']);

            Route::prefix('analytics')->group(function () {
                Route::get('/overview', [AdminAnalyticsController::class, 'overview']);
                Route::get('/sales', [AdminAnalyticsController::class, 'sales']);
                Route::get('/orders', [AdminAnalyticsController::class, 'orders']);
                Route::get('/products', [AdminAnalyticsController::class, 'products']);
                Route::get('/categories', [AdminAnalyticsController::class, 'categories']);
                Route::get('/branches', [AdminAnalyticsController::class, 'branches']);
                Route::get('/customers', [AdminAnalyticsController::class, 'customers']);
                Route::get('/payment-methods', [AdminAnalyticsController::class, 'paymentMethods']);
                Route::get('/delivery-zones', [AdminAnalyticsController::class, 'deliveryZones']);
                Route::get('/export', [AdminAnalyticsController::class, 'export']);
            });

            Route::middleware('role:super_admin')->group(function () {
                Route::get('/users', [AdminUserController::class, 'index']);
                Route::get('/users/{user}', [AdminUserController::class, 'show']);
                Route::patch('/users/{user}', [AdminUserController::class, 'update']);

                Route::get('/categories', [AdminCategoryController::class, 'index']);
                Route::post('/categories', [AdminCategoryController::class, 'store']);
                Route::get('/categories/{category}', [AdminCategoryController::class, 'show']);
                Route::patch('/categories/{category}', [AdminCategoryController::class, 'update']);
                Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

                Route::get('/products', [AdminProductController::class, 'index']);
                Route::post('/products', [AdminProductController::class, 'store']);
                Route::get('/products/{product:id}', [AdminProductController::class, 'show']);
                Route::patch('/products/{product:id}', [AdminProductController::class, 'update']);
                Route::delete('/products/{product:id}', [AdminProductController::class, 'destroy']);
                Route::post('/products/{product:id}/variants', [AdminProductController::class, 'storeVariant']);
                Route::patch('/products/{product:id}/variants/{variant}', [AdminProductController::class, 'updateVariant']);
                Route::post('/products/{product:id}/images', [AdminProductController::class, 'storeImage']);
                Route::post('/products/{product:id}/images/{image}/primary', [AdminProductController::class, 'setPrimaryImage']);
                Route::delete('/products/{product:id}/images/{image}', [AdminProductController::class, 'destroyImage']);

                Route::get('/add-on-groups', [AdminAddOnGroupController::class, 'index']);
                Route::post('/add-on-groups', [AdminAddOnGroupController::class, 'store']);
                Route::get('/add-on-groups/{add_on_group}', [AdminAddOnGroupController::class, 'show']);
                Route::patch('/add-on-groups/{add_on_group}', [AdminAddOnGroupController::class, 'update']);
                Route::delete('/add-on-groups/{add_on_group}', [AdminAddOnGroupController::class, 'destroy']);
                Route::post('/add-on-groups/{add_on_group}/options', [AdminAddOnGroupController::class, 'storeOption']);
                Route::patch('/add-on-groups/{add_on_group}/options/{add_on}', [AdminAddOnGroupController::class, 'updateOption']);

                Route::get('/branches', [AdminBranchController::class, 'index']);
                Route::post('/branches', [AdminBranchController::class, 'store']);
                Route::get('/branches/{branch}', [AdminBranchController::class, 'show']);
                Route::patch('/branches/{branch}', [AdminBranchController::class, 'update']);
                Route::delete('/branches/{branch}', [AdminBranchController::class, 'destroy']);

                Route::get('/delivery-zones', [AdminDeliveryZoneController::class, 'index']);
                Route::post('/delivery-zones', [AdminDeliveryZoneController::class, 'store']);
                Route::get('/delivery-zones/{delivery_zone}', [AdminDeliveryZoneController::class, 'show']);
                Route::patch('/delivery-zones/{delivery_zone}', [AdminDeliveryZoneController::class, 'update']);
                Route::delete('/delivery-zones/{delivery_zone}', [AdminDeliveryZoneController::class, 'destroy']);

                Route::get('/offers', [AdminOfferController::class, 'index']);
                Route::post('/offers', [AdminOfferController::class, 'store']);
                Route::get('/offers/{offer}', [AdminOfferController::class, 'show']);
                Route::patch('/offers/{offer}', [AdminOfferController::class, 'update']);
                Route::delete('/offers/{offer}', [AdminOfferController::class, 'destroy']);

                Route::get('/gallery', [AdminGalleryController::class, 'index']);
                Route::post('/gallery', [AdminGalleryController::class, 'store']);
                Route::get('/gallery/{gallery_item}', [AdminGalleryController::class, 'show']);
                Route::patch('/gallery/{gallery_item}', [AdminGalleryController::class, 'update']);
                Route::delete('/gallery/{gallery_item}', [AdminGalleryController::class, 'destroy']);
                Route::patch('/gallery/{gallery_item}/status', [AdminGalleryController::class, 'toggleStatus']);

                Route::get('/menu-book', [AdminMenuBookController::class, 'index']);
                Route::post('/menu-book', [AdminMenuBookController::class, 'store']);
                Route::get('/menu-book/{menu_book_page}', [AdminMenuBookController::class, 'show']);
                Route::patch('/menu-book/{menu_book_page}', [AdminMenuBookController::class, 'update']);
                Route::delete('/menu-book/{menu_book_page}', [AdminMenuBookController::class, 'destroy']);
                Route::patch('/menu-book/{menu_book_page}/status', [AdminMenuBookController::class, 'toggleStatus']);

                Route::get('/homepage', [AdminHomepageController::class, 'show']);
                Route::patch('/homepage/sections/reorder', [AdminHomepageController::class, 'reorderSections']);
                Route::patch('/homepage/sections/{homepage_section:key}', [AdminHomepageController::class, 'updateSection']);
                Route::post('/homepage/features', [AdminHomepageController::class, 'storeFeature']);
                Route::patch('/homepage/features/{homepage_feature}', [AdminHomepageController::class, 'updateFeature']);
                Route::delete('/homepage/features/{homepage_feature}', [AdminHomepageController::class, 'destroyFeature']);
                Route::post('/homepage/cta-cards', [AdminHomepageController::class, 'storeCtaCard']);
                Route::patch('/homepage/cta-cards/{homepage_cta_card}', [AdminHomepageController::class, 'updateCtaCard']);
                Route::delete('/homepage/cta-cards/{homepage_cta_card}', [AdminHomepageController::class, 'destroyCtaCard']);
                Route::post('/homepage/showcase-items', [AdminHomepageController::class, 'storeShowcaseItem']);
                Route::patch('/homepage/showcase-items/{homepage_showcase_item}', [AdminHomepageController::class, 'updateShowcaseItem']);
                Route::delete('/homepage/showcase-items/{homepage_showcase_item}', [AdminHomepageController::class, 'destroyShowcaseItem']);

                Route::get('/reviews', [AdminReviewController::class, 'index']);
                Route::get('/reviews/{review}', [AdminReviewController::class, 'show']);
                Route::patch('/reviews/{review}', [AdminReviewController::class, 'update']);
                Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy']);

                Route::get('/contact-messages', [AdminContactMessageController::class, 'index']);
                Route::get('/contact-messages/{contact_message}', [AdminContactMessageController::class, 'show']);
                Route::patch('/contact-messages/{contact_message}', [AdminContactMessageController::class, 'update']);
                Route::delete('/contact-messages/{contact_message}', [AdminContactMessageController::class, 'destroy']);

                Route::get('/settings', [AdminSettingController::class, 'show']);
                Route::patch('/settings', [AdminSettingController::class, 'update']);
                Route::post('/settings/logo', [AdminSettingController::class, 'updateLogo']);
                Route::delete('/settings/logo', [AdminSettingController::class, 'destroyLogo']);
                Route::patch('/settings/popup', [AdminSettingController::class, 'updatePopup']);
                Route::patch('/settings/payment', [AdminSettingController::class, 'updatePayment']);
                Route::post('/settings/payment/jazzcash/qr', [AdminSettingController::class, 'updateJazzcashQrImage']);
                Route::delete('/settings/payment/jazzcash/qr', [AdminSettingController::class, 'destroyJazzcashQrImage']);
                Route::post('/settings/popup/image', [AdminSettingController::class, 'updatePopupImage']);
                Route::delete('/settings/popup/image', [AdminSettingController::class, 'destroyPopupImage']);
            });
        });
    });

    Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('/admin/ping', function () {
        return response()->json(['message' => 'admin']);
    })->middleware('role:super_admin,manager');
});
