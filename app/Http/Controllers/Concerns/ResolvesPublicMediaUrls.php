<?php

namespace App\Http\Controllers\Concerns;

trait ResolvesPublicMediaUrls
{
    protected function resolveImageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim(config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}
