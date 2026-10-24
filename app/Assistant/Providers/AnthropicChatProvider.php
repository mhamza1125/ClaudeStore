<?php

namespace App\Assistant\Providers;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Assistant\AssistantUnavailable;
use App\Assistant\Contracts\ChatProvider;
use Illuminate\Support\Facades\Log;
use Psr\Http\Client\ClientInterface;

/**
 * Claude via the official Anthropic PHP SDK (Messages API).
 *
 * - The system prompt is sent as a cached block: it is identical for every visitor
 *   until the catalogue or settings change, so repeat requests read it from cache.
 * - Server-side refusal fallback is enabled ("default" routing); a refused turn
 *   still gets a polite, non-committal answer.
 * - Visitor message content is never logged.
 */
class AnthropicChatProvider implements ChatProvider
{
    private const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model = 'claude-opus-5-5',
        private readonly string $effort = 'low',
        private readonly int $maxTokens = 2048,
        private readonly float $timeout = 30.0,
        private readonly ?ClientInterface $transporter = null, // injectable for tests
    ) {}

    public function name(): string
    {
        return 'anthropic';
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    public function model(): ?string
    {
        return $this->model;
    }

    public function reply(string $system, array $messages): string
    {
        if (! $this->isConfigured()) {
            throw new AssistantUnavailable('ANTHROPIC_API_KEY is not set.');
        }

        $client = new Client(apiKey: $this->apiKey, requestOptions: array_filter([
            'timeout' => $this->timeout,
            'maxRetries' => 1,
            'transporter' => $this->transporter,
        ], fn ($v) => $v !== null));

        try {
            $message = $client->beta->messages->create(
                maxTokens: $this->maxTokens,
                messages: $messages,
                model: $this->model,
                fallbacks: 'default',
                outputConfig: ['effort' => $this->effort],
                system: [['type' => 'text', 'text' => $system, 'cacheControl' => ['type' => 'ephemeral']]],
                betas: [self::FALLBACK_BETA],
            );
        } catch (AuthenticationException $e) {
            Log::error('Assistant: Anthropic authentication failed — check ANTHROPIC_API_KEY.');
            throw new AssistantUnavailable('Authentication failed.', previous: $e);
        } catch (RateLimitException $e) {
            Log::warning('Assistant: Anthropic rate limit reached.');
            throw new AssistantUnavailable('Rate limited.', previous: $e);
        } catch (BadRequestException $e) {
            Log::error('Assistant: Anthropic rejected the request.', ['type' => $e->type?->value]);
            throw new AssistantUnavailable('Bad request.', previous: $e);
        } catch (APIStatusException $e) {
            Log::warning('Assistant: Anthropic API error.', ['type' => $e->type?->value]);
            throw new AssistantUnavailable('API error.', previous: $e);
        } catch (APIConnectionException $e) {
            Log::warning('Assistant: could not reach the Anthropic API.');
            throw new AssistantUnavailable('Connection error.', previous: $e);
        }

        if ($message->stopReason === 'refusal') {
            return "I'm not able to help with that here. For product, pricing or order questions, please request a quote or contact our team directly.";
        }

        $text = collect($message->content)->filter(fn ($block) => $block->type === 'text')->map(fn ($block) => $block->text)->implode("\n\n");
        if (trim($text) === '') {
            throw new AssistantUnavailable('Empty response.');
        }

        return trim($text);
    }
}
