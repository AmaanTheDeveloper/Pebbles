<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\MergeGuestCartIntoUserCart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\GuestCartCookie;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request, MergeGuestCartIntoUserCart $mergeGuestCart): JsonResponse
    {
        $guestToken = GuestCartCookie::read($request);

        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        $mergeGuestCart->handle($user, $guestToken);

        return (new UserResource($user->loadMissing(['branch', 'defaultAddress'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
