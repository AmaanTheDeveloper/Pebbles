<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
    }
}
