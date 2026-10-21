<?php

namespace App\Images;

final class ProviderResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $binary = null,
        public readonly ?string $mimeType = null,
        public readonly ?string $error = null,
        public readonly ?string $notes = null,
    ) {}

    public static function failure(string $error): self
    {
        return new self(false, error: $error);
    }
}
