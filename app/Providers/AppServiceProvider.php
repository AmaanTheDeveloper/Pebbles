<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Category;
use App\Models\HomepageCtaCard;
use App\Models\HomepageFeature;
use App\Models\HomepageSection;
use App\Models\HomepageShowcaseItem;
use App\Models\User;
use App\Support\PublicCatalogCache;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureAuthorization();
        $this->configureRateLimiting();
        $this->configurePublicCatalogCacheInvalidation();
        $this->configurePasswordResetUrls();
        $this->configureEmailVerificationUrls();
    }

    protected function configurePasswordResetUrls(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $email = urlencode((string) $notifiable->getEmailForPasswordReset());

            return rtrim((string) config('app.url'), '/').'/reset-password?token='.urlencode($token).'&email='.$email;
        });
    }

    protected function configureEmailVerificationUrls(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable): string {
            $apiUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            return str_replace('/api/email/verify/', '/verify-email/', $apiUrl);
        });
    }

    protected function configurePublicCatalogCacheInvalidation(): void
    {
        Category::saved(fn () => PublicCatalogCache::forgetCategories());
        Category::deleted(fn () => PublicCatalogCache::forgetCategories());

        Branch::saved(fn () => PublicCatalogCache::forgetBranches());
        Branch::deleted(fn () => PublicCatalogCache::forgetBranches());

        foreach ([HomepageSection::class, HomepageFeature::class, HomepageCtaCard::class, HomepageShowcaseItem::class] as $model) {
            $model::saved(fn () => PublicCatalogCache::forgetHomepage());
            $model::deleted(fn () => PublicCatalogCache::forgetHomepage());
        }
    }

    protected function configureAuthorization(): void
    {
        Gate::define('access-admin', function (User $user): bool {
            return $user->canAccessAdmin();
        });

        Gate::define('manage-users', function (User $user): bool {
            return $user->isSuperAdmin();
        });

        Gate::define('manage-global-settings', function (User $user): bool {
            return $user->isSuperAdmin();
        });

        Gate::define('manage-catalog', function (User $user): bool {
            return $user->isSuperAdmin();
        });

        Gate::define('manage-branches', function (User $user): bool {
            return $user->isSuperAdmin();
        });

        Gate::define('role', function (User $user, UserRole|string $role): bool {
            $requiredRole = $role instanceof UserRole
                ? $role
                : UserRole::tryFrom($role);

            return $requiredRole !== null && $user->hasRole($requiredRole);
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->string('email')->lower().'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('reviews', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });

        RateLimiter::for('contact-messages', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('verification', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('coupon-apply', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('cart-mutations', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
