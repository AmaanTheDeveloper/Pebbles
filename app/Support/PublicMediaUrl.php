<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicMediaUrl
{
    /**
     * Build a public disk URL for a relative media path, or null when blank.
     *
     * Honours optional MEDIA_URL (CDN) override from config('pebbles.media_url').
     * Otherwise uses Storage::disk(config('pebbles.media_disk'))->url().
     */
    public static function fromPath(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $relative = ltrim($path, '/');
        $cdnBase = config('pebbles.media_url');

        if (filled($cdnBase)) {
            return rtrim((string) $cdnBase, '/').'/'.$relative;
        }

        $disk = (string) config('pebbles.media_disk', 'public');

        return Storage::disk($disk)->url($relative);
    }
}
