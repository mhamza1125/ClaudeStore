<?php

namespace App\Images\Providers;

use App\Images\Contracts\ImageProvider;
use App\Images\ProviderResult;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Background removal via the remove.bg HTTP API (https://www.remove.bg/api).
 * Requires REMOVEBG_API_KEY. Only covered by faked-HTTP tests in this repository;
 * verify against the live API before relying on it. Generation is not supported.
 */
class RemoveBgProvider implements ImageProvider
{
    public const ENDPOINT = 'https://api.remove.bg/v1.0/removebg';

    public function __construct(private ?string $apiKey) {}

    public function name(): string
    {
        return 'removebg';
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    public function capabilities(): array
    {
        return ['remove_background'];
    }

    public function removeBackground(string $imageBinary, string $mimeType): ProviderResult
    {
        if (! $this->isConfigured()) {
            return ProviderResult::failure('REMOVEBG_API_KEY is not set.');
        }

        try {
            $response = Http::timeout(60)
                ->withHeaders(['X-Api-Key' => $this->apiKey])
                ->attach('image_file', $imageBinary, 'upload.'.($mimeType === 'image/png' ? 'png' : 'jpg'))
                ->post(self::ENDPOINT, ['size' => 'auto', 'format' => 'png']);
        } catch (Throwable $e) {
            return ProviderResult::failure('remove.bg request failed: '.$e->getMessage());
        }

        if (! $response->successful()) {
            $message = $response->json('errors.0.title') ?? ('HTTP '.$response->status());

            return ProviderResult::failure('remove.bg error: '.$message);
        }

        return new ProviderResult(true, $response->body(), 'image/png', notes: 'Background removed by remove.bg');
    }

    public function generateView(array $referenceBinaries, string $viewType, string $instructions): ProviderResult
    {
        return ProviderResult::failure('remove.bg does not generate images.');
    }
}
