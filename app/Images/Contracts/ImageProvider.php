<?php

namespace App\Images\Contracts;

use App\Images\ProviderResult;

/**
 * Provider-independent image processing. Implementations receive the binary of the
 * ORIGINAL photograph and return a new image; they never overwrite originals.
 * Every result is stored as a draft ("pending_review") that staff must approve.
 */
interface ImageProvider
{
    /** Machine name, e.g. "removebg". */
    public function name(): string;

    /** True only when credentials are present. Callers must check before use. */
    public function isConfigured(): bool;

    /** @return array<int,string> operations supported, e.g. ['remove_background', 'generate_view'] */
    public function capabilities(): array;

    public function removeBackground(string $imageBinary, string $mimeType): ProviderResult;

    /**
     * Generate an alternate view or lifestyle image from reference photos.
     *
     * @param  array<int,string>  $referenceBinaries
     */
    public function generateView(array $referenceBinaries, string $viewType, string $instructions): ProviderResult;
}
