<?php

use App\Services\QuoteWorkflow;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('quotes:expire', function (QuoteWorkflow $workflow) {
    $count = $workflow->expireOverdue();
    $this->info("{$count} quotation(s) marked as expired.");
})->purpose('Mark sent quotations past their validity date as expired');

Schedule::command('quotes:expire')->dailyAt('00:15');
Schedule::command('queue:prune-failed --hours=720')->daily();

// Live check of the chatbot's AI provider with the server's real configuration.
Artisan::command('assistant:check', function (\App\Assistant\Contracts\ChatProvider $provider) {
    $this->line('Provider: '.$provider->name().'   Model: '.($provider->model() ?? '—'));
    if (! $provider->isConfigured()) {
        $this->error('Not configured: set AI_DEFAULT_PROVIDER and the provider credentials in .env (see docs/CHATBOT.md).');

        return 1;
    }
    $started = microtime(true);
    try {
        $reply = $provider->reply('You are a connectivity check. Reply with the single word OK.', [['role' => 'user', 'content' => 'Connectivity check']]);
    } catch (\App\Assistant\AssistantUnavailable $e) {
        $this->error('Request failed: '.$e->getMessage());

        return 1;
    }
    $this->info(sprintf('Live response received in %d ms: “%s”', (microtime(true) - $started) * 1000, \Illuminate\Support\Str::limit($reply, 80)));

    return 0;
})->purpose('Send a test message to the configured chatbot AI provider');
