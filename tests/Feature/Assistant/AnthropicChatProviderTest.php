<?php

namespace Tests\Feature\Assistant;

use App\Assistant\AssistantUnavailable;
use App\Assistant\Providers\AnthropicChatProvider;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

/** Exercises the real Anthropic PHP SDK with a mocked HTTP transport (no network). */
class AnthropicChatProviderTest extends TestCase
{
    private array $sent = [];

    private function provider(array $responses, ?string $key = 'sk-ant-test-key'): AnthropicChatProvider
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->sent));

        return new AnthropicChatProvider($key, 'claude-opus-5-5', 'low', 2048, 10.0, new Guzzle(['handler' => $stack]));
    }

    private function message(array $content, string $stop = 'end_turn'): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5-5',
            'content' => $content, 'stop_reason' => $stop, 'stop_sequence' => null, 'stop_details' => null,
            'usage' => ['input_tokens' => 120, 'output_tokens' => 20],
        ]));
    }

    public function test_sends_a_server_side_request_with_cached_system_prompt_and_returns_text(): void
    {
        $provider = $this->provider([$this->message([['type' => 'text', 'text' => 'Our MOQ for wallets is 1. See /products/classic-bifold-wallet']])]);

        $reply = $provider->reply('SYSTEM PROMPT', [['role' => 'user', 'content' => 'What is the MOQ?']]);

        $this->assertSame('Our MOQ for wallets is 1. See /products/classic-bifold-wallet', $reply);
        $request = $this->sent[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringEndsWith('/v1/messages', $request->getUri()->getPath());
        // The PHP SDK sends the API key as a bearer credential.
        $this->assertSame('Bearer sk-ant-test-key', $request->getHeaderLine('Authorization'));
        $this->assertStringContainsString('server-side-fallback-2026-07-01', $request->getHeaderLine('anthropic-beta'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('claude-opus-5-5', $body['model']);
        $this->assertSame('default', $body['fallbacks']);
        $this->assertSame(['effort' => 'low'], $body['output_config']);
        $this->assertSame([['type' => 'text', 'text' => 'SYSTEM PROMPT', 'cache_control' => ['type' => 'ephemeral']]], $body['system']);
        $this->assertSame([['role' => 'user', 'content' => 'What is the MOQ?']], $body['messages']);
    }

    public function test_ignores_non_text_blocks(): void
    {
        $provider = $this->provider([$this->message([
            ['type' => 'thinking', 'thinking' => '', 'signature' => 'sig'],
            ['type' => 'text', 'text' => 'Hello.'],
        ])]);
        $this->assertSame('Hello.', $provider->reply('S', [['role' => 'user', 'content' => 'Hi']]));
    }

    public function test_refusal_returns_a_safe_message(): void
    {
        $provider = $this->provider([$this->message([], 'refusal')]);
        $this->assertStringContainsString('request a quote', $provider->reply('S', [['role' => 'user', 'content' => 'x']]));
    }

    public function test_api_errors_become_assistant_unavailable(): void
    {
        foreach ([[401, 'authentication_error'], [429, 'rate_limit_error'], [400, 'invalid_request_error'], [500, 'api_error']] as [$status, $type]) {
            $error = fn () => new Response($status, ['Content-Type' => 'application/json'], json_encode(['type' => 'error', 'error' => ['type' => $type, 'message' => 'x']]));
            try {
                // Retryable statuses are retried once (maxRetries = 1), so queue two responses.
                $this->provider([$error(), $error()])->reply('S', [['role' => 'user', 'content' => 'x']]);
                $this->fail("No exception for HTTP {$status}");
            } catch (AssistantUnavailable) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_missing_key_is_not_configured_and_never_calls_the_api(): void
    {
        $provider = $this->provider([], key: null);
        $this->assertFalse($provider->isConfigured());
        $this->expectException(AssistantUnavailable::class);
        $provider->reply('S', [['role' => 'user', 'content' => 'x']]);
    }
}
