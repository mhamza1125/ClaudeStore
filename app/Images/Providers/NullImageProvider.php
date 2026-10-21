<?php

namespace App\Images\Providers;

use App\Images\Contracts\ImageProvider;
use App\Images\ProviderResult;

/**
 * Default provider when no image service is configured. It never pretends to
 * produce an image: every call fails with an explanatory message, and the admin
 * UI offers the manual-upload workflow instead.
 */
class NullImageProvider implements ImageProvider
{
    public function name(): string
    {
        return 'null';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function capabilities(): array
    {
        return [];
    }

    public function removeBackground(string $imageBinary, string $mimeType): ProviderResult
    {
        return ProviderResult::failure('No image provider is configured. Upload an edited image manually instead (see docs/IMAGES.md).');
    }

    public function generateView(array $referenceBinaries, string $viewType, string $instructions): ProviderResult
    {
        return ProviderResult::failure('No image generation provider is configured.');
    }
}
