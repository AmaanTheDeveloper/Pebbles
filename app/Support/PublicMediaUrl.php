<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicMediaUrl
{
    /**
     * Build a public disk URL for a relative media path, or null when blank.
     */
    public static function fromPath(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }
}
