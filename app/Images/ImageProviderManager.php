<?php

namespace App\Images;

use App\Images\Contracts\ImageProvider;
use App\Images\Providers\NullImageProvider;
use App\Images\Providers\RemoveBgProvider;

/** Resolves the configured provider (IMAGE_PROVIDER env). Add new providers here. */
class ImageProviderManager
{
    public static function make(?string $name): ImageProvider
    {
        return match ($name) {
            'removebg' => new RemoveBgProvider(config('store.images.removebg_key')),
            default => new NullImageProvider,
        };
    }
}
