<?php

namespace App\Assistant\Providers;

use App\Assistant\AssistantUnavailable;
use App\Assistant\Contracts\ChatProvider;

/** Used when no AI provider is configured. It never fabricates answers. */
class NullChatProvider implements ChatProvider
{
    public function name(): string
    {
        return 'null';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function reply(string $system, array $messages): string
    {
        throw new AssistantUnavailable('No AI provider is configured.');
    }
}
