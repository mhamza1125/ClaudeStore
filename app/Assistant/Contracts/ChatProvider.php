<?php

namespace App\Assistant\Contracts;

use App\Assistant\AssistantUnavailable;

/**
 * Provider-independent chat completion for the website assistant. Implementations
 * run server-side only; credentials never reach the browser.
 */
interface ChatProvider
{
    public function name(): string;

    /** True only when credentials are present. */
    public function isConfigured(): bool;

    /** Model identifier used for replies (for status displays). */
    public function model(): ?string;

    /**
     * @param  list<array{role: 'user'|'assistant', content: string}>  $messages  oldest first, ending with the visitor's message
     *
     * @throws AssistantUnavailable when the provider cannot produce an answer
     */
    public function reply(string $system, array $messages): string;
}
