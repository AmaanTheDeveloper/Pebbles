<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\MergeGuestCartIntoUserCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Support\GuestCartCookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, MergeGuestCartIntoUserCart $mergeGuestCart): JsonResponse
    {
        $guestToken = GuestCartCookie::read($request);

        $request->authenticate();

        $request->session()->regenerate();

        $mergeGuestCart->handle($request->user(), $guestToken);

        return (new UserResource($request->user()->loadMissing(['branch', 'defaultAddress'])))->response();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
