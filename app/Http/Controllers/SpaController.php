<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;

class SpaController extends Controller
{
    /**
     * Serve the React SPA shell. Unknown customer paths return HTTP 404
     * with the same shell so the branded Not Found page can render.
     * API routes are never handled here.
     */
    public function __invoke(?string $any = null): Response|View
    {
        $path = trim((string) $any, '/');
        $status = $this->isKnownSpaPath($path) ? 200 : 404;

        return response()
            ->view('app', [
                'httpStatus' => $status,
            ], $status);
    }

    private function isKnownSpaPath(string $path): bool
    {
        if ($path === '') {
            return true;
        }

        // If an unmatched API/framework path reaches the SPA catch-all, keep JSON/API 404 behavior.
        if (str_starts_with($path, 'api/') || $path === 'api' || str_starts_with($path, 'sanctum/')) {
            abort(response()->json(['message' => 'Not found.'], 404));
        }

        // Public files / offline fallback are served by the web server; treat as known if reached.
        if ($path === 'offline.html' || $path === 'sw.js' || str_starts_with($path, 'build/') || str_starts_with($path, 'storage/')) {
            return true;
        }

        $patterns = [
            'about',
            'gallery',
            'reviews',
            'contact',
            'locations',
            'order',
            'menu',
            'menu/[^/]+',
            'menu-book',
            'product/[^/]+',
            'offers',
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'verify-email',
            'verify-email/[^/]+/[^/]+',
            'cart',
            'checkout',
            'orders',
            'orders/[^/]+/track',
            'order/[^/]+',
            'order/[^/]+/modify',
            'profile',
            'order-confirmation/[^/]+',
            'admin',
            'admin(?:/.*)?',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match('#^'.$pattern.'$#i', $path) === 1) {
                return true;
            }
        }

        return false;
    }
}
