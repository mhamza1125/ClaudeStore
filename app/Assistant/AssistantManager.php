<?php

namespace App\Assistant;

use App\Assistant\Contracts\ChatProvider;
use App\Assistant\Providers\AnthropicChatProvider;
use App\Assistant\Providers\NullChatProvider;

/** Resolves the configured chat provider (ASSISTANT_PROVIDER). Add new providers here. */
class AssistantManager
{
    public static function make(?string $name): ChatProvider
    {
        $c = config('store.assistant');

        return match ($name) {
            'anthropic' => new AnthropicChatProvider(
                apiKey: config('services.anthropic.key'),
                model: $c['model'],
                effort: $c['effort'],
                maxTokens: $c['max_tokens'],
                timeout: $c['timeout'],
            ),
            default => new NullChatProvider,
        };
    }
}
