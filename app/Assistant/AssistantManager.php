<?php

namespace App\Assistant;

use App\Assistant\Contracts\ChatProvider;
use App\Assistant\Providers\AnthropicChatProvider;
use App\Assistant\Providers\CloudflareChatProvider;
use App\Assistant\Providers\NullChatProvider;

/** Resolves the configured chat provider (AI_DEFAULT_PROVIDER). Add new providers here. */
class AssistantManager
{
    public static function make(?string $name): ChatProvider
    {
        $c = config('store.assistant');

        return match ($name) {
            'cloudflare' => new CloudflareChatProvider(
                accountId: config('services.cloudflare.account_id'),
                apiToken: config('services.cloudflare.api_token'),
                model: (string) $c['cloudflare']['model'],
                baseUrl: (string) $c['cloudflare']['base_url'],
                maxTokens: (int) $c['max_tokens'],
                temperature: (float) $c['cloudflare']['temperature'],
                timeout: (float) $c['timeout'],
            ),
            'anthropic' => new AnthropicChatProvider(
                apiKey: config('services.anthropic.key'),
                model: $c['anthropic']['model'],
                effort: $c['anthropic']['effort'],
                maxTokens: (int) $c['max_tokens'],
                timeout: (float) $c['timeout'],
            ),
            default => new NullChatProvider,
        };
    }
}
