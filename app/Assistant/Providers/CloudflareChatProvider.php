<?php

namespace App\Assistant\Providers;

use App\Assistant\AssistantUnavailable;
use App\Assistant\Contracts\ChatProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Workers AI via its REST API:
 *   POST {base}/accounts/{account_id}/ai/run/{model}
 *   Authorization: Bearer {api_token}
 *   {"messages": [{"role": "system"|"user"|"assistant", "content": "…"}], "max_tokens": …, "temperature": …}
 * Text-generation models answer {"success": true, "result": {"response": "…"}}; the
 * OpenAI-style result.choices[0].message.content shape is accepted as well.
 *
 * The token is only ever sent in the Authorization header to Cloudflare. Neither it nor
 * visitor messages are logged; failures log the status and Cloudflare's error codes.
 */
class CloudflareChatProvider implements ChatProvider
{
    public function __construct(
        private readonly ?string $accountId,
        private readonly ?string $apiToken,
        private readonly string $model = '@cf/meta/llama-3.3-70b-instruct-fp8-fast',
        private readonly string $baseUrl = 'https://api.cloudflare.com/client/v4',
        private readonly int $maxTokens = 1024,
        private readonly float $temperature = 0.3,
        private readonly float $timeout = 30.0,
    ) {}

    public function name(): string
    {
        return 'cloudflare';
    }

    public function isConfigured(): bool
    {
        return filled($this->accountId) && filled($this->apiToken) && $this->validModel();
    }

    public function model(): ?string
    {
        return $this->model;
    }

    public function endpoint(): string
    {
        return rtrim($this->baseUrl, '/').'/accounts/'.rawurlencode((string) $this->accountId).'/ai/run/'.$this->model;
    }

    public function reply(string $system, array $messages): string
    {
        if (! filled($this->accountId) || ! filled($this->apiToken)) {
            throw new AssistantUnavailable('CLOUDFLARE_ACCOUNT_ID and CLOUDFLARE_API_TOKEN must both be set.');
        }
        if (! $this->validModel()) {
            throw new AssistantUnavailable("CLOUDFLARE_DEFAULT_MODEL “{$this->model}” is not a valid Workers AI model ID.");
        }

        $payload = [
            'messages' => [['role' => 'system', 'content' => $system], ...$messages],
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
        ];
        $send = fn () => Http::withToken($this->apiToken)->acceptJson()->connectTimeout(5)->timeout($this->timeout)
            ->post($this->endpoint(), $payload);

        // One retry for transient failures (network errors and 5xx); 4xx are final.
        try {
            try {
                $response = $send();
            } catch (ConnectionException) {
                usleep(400_000);
                $response = $send();
            }
            if ($response->serverError()) {
                usleep(400_000);
                $response = $send();
            }
        } catch (RequestException $e) {
            // Some transports (e.g. an HTTP proxy refusing CONNECT) surface the
            // failure as an exception carrying the response; treat it the same.
            $response = $e->response;
        } catch (ConnectionException $e) {
            Log::warning('Assistant: could not reach Cloudflare Workers AI.');
            throw new AssistantUnavailable('Could not reach Cloudflare Workers AI.', previous: $e);
        }

        if (! $response->successful() || $response->json('success') === false) {
            throw new AssistantUnavailable($this->describeFailure($response));
        }

        $text = $response->json('result.response');
        if (! is_string($text)) {
            $text = $response->json('result.choices.0.message.content');
        }
        if (! is_string($text) || trim($text) === '') {
            Log::warning('Assistant: Cloudflare Workers AI returned no text.', ['model' => $this->model]);
            throw new AssistantUnavailable('Cloudflare Workers AI returned an empty or unrecognised response.');
        }

        return trim($text);
    }

    private function validModel(): bool
    {
        // e.g. @cf/meta/llama-3.3-70b-instruct-fp8-fast or @hf/… ; keeps the URL path well-formed.
        return (bool) preg_match('#^@[a-z0-9-]+(/[A-Za-z0-9._-]+)+$#', $this->model);
    }

    private function describeFailure(Response $response): string
    {
        $status = $response->status();
        $errors = collect($response->json('errors') ?? [])
            ->map(fn ($e) => trim(($e['code'] ?? '').' '.\Illuminate\Support\Str::limit((string) ($e['message'] ?? ''), 160)))
            ->filter()->implode('; ');
        $hint = match (true) {
            $status === 401, $status === 403 => 'authentication failed — check CLOUDFLARE_API_TOKEN (Workers AI permission) and CLOUDFLARE_ACCOUNT_ID',
            $status === 404 => 'not found — check CLOUDFLARE_ACCOUNT_ID and that the model ID exists',
            $status === 429 => 'rate limited by Cloudflare',
            $status >= 500 => 'Cloudflare service error',
            default => 'request rejected',
        };
        Log::warning('Assistant: Cloudflare Workers AI request failed.', ['status' => $status, 'model' => $this->model, 'errors' => $errors]);

        return "Cloudflare Workers AI {$status}: {$hint}".($errors !== '' ? " ({$errors})" : '').'.';
    }
}
