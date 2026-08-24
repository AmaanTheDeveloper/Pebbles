<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ChangeUserPassword;
use App\Actions\DeleteUserAccount;
use App\Actions\UpdateUserProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Support\ProfileSettings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function update(
        UpdateProfileRequest $request,
        UpdateUserProfile $updateUserProfile,
    ): JsonResponse {
        $originalEmail = $request->user()->email;
        $user = $updateUserProfile->handle($request->user(), $request->validated());

        if ($originalEmail !== $user->email) {
            event(new Registered($user));
        }

        return response()->json([
            'data' => (new UserResource($user->loadMissing(['branch', 'defaultAddress'])))->resolve(),
            'message' => 'Profile updated successfully.',
        ]);
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        ProfileSettings::storePhoto($user, $request->file('photo'));

        return response()->json([
            'data' => (new UserResource($user->fresh(['branch', 'defaultAddress'])))->resolve(),
            'message' => 'Profile photo updated successfully.',
        ]);
    }

    public function destroyPhoto(Request $request): JsonResponse
    {
        ProfileSettings::removePhoto($request->user());

        return response()->json([
            'data' => (new UserResource($request->user()->fresh(['branch', 'defaultAddress'])))->resolve(),
            'message' => 'Profile photo removed successfully.',
        ]);
    }

    public function updatePassword(
        ChangePasswordRequest $request,
        ChangeUserPassword $changeUserPassword,
    ): JsonResponse {
        $user = $changeUserPassword->handle(
            $request->user(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'data' => (new UserResource($user->loadMissing(['branch', 'defaultAddress'])))->resolve(),
            'message' => 'Password changed successfully.',
        ]);
    }

    public function destroy(
        DeleteAccountRequest $request,
        DeleteUserAccount $deleteUserAccount,
    ): JsonResponse {
        $deleteUserAccount->handle($request->user());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Account deleted successfully.',
        ], Response::HTTP_OK);
    }
}
