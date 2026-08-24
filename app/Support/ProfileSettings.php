<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileSettings
{
    /**
     * @return array{url: string, path: string}|null
     */
    public static function photoPayload(?User $user): ?array
    {
        $path = $user?->profile_photo_path;

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $url = PublicMediaUrl::fromPath($path);

        if ($url === null) {
            return null;
        }

        return [
            'url' => $url,
            'path' => $path,
        ];
    }

    public static function storePhoto(User $user, UploadedFile $file): string
    {
        $previousPath = $user->profile_photo_path;
        $path = $file->store('profile-photos/'.$user->id, 'public');

        $user->forceFill(['profile_photo_path' => $path])->save();

        self::deleteStoredPhoto($previousPath, $user->id);

        return $path;
    }

    public static function removePhoto(User $user): void
    {
        $previousPath = $user->profile_photo_path;
        $user->forceFill(['profile_photo_path' => null])->save();
        self::deleteStoredPhoto($previousPath, $user->id);
    }

    public static function deleteStoredPhoto(?string $path, ?int $userId = null): void
    {
        if ($path === null || $path === '' || ! str_starts_with($path, 'profile-photos/')) {
            return;
        }

        if ($userId !== null && ! str_starts_with($path, 'profile-photos/'.$userId.'/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
