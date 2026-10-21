<?php

namespace App\Http\Controllers;

use App\Assistant\AssistantUnavailable;
use App\Assistant\BusinessContext;
use App\Assistant\Contracts\ChatProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Website chatbot endpoint. The conversation lives in the visitor's server session
 * (not the database); only the system prompt, recent history and the new message are
 * sent to the AI provider.
 */
class AssistantController extends Controller
{
    public const SESSION_KEY = 'assistant.history';

    public function __construct(private ChatProvider $provider) {}

    public function send(Request $request, BusinessContext $context): JsonResponse
    {
        abort_unless(setting('chatbot.enabled'), 404);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:'.(int) config('store.assistant.max_message_length', 1000)],
        ]);
        $text = trim(preg_replace('/[^\P{C}\n\t]/u', '', $data['message']));
        if ($text === '') {
            return response()->json(['message' => 'Please type a question.'], 422);
        }

        if (! $this->provider->isConfigured()) {
            return $this->unavailable();
        }

        $history = self::history($request);
        $messages = [...$history, ['role' => 'user', 'content' => $text]];

        try {
            $reply = $this->provider->reply($context->systemPrompt(), $messages);
        } catch (AssistantUnavailable) {
            return $this->unavailable();
        }

        $messages[] = ['role' => 'assistant', 'content' => $reply];
        $request->session()->put(self::SESSION_KEY, self::trim($messages));

        return response()->json(['reply' => $reply]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return response()->json(['ok' => true]);
    }

    /** @return list<array{role: string, content: string}> */
    public static function history(Request $request): array
    {
        return array_values((array) $request->session()->get(self::SESSION_KEY, []));
    }

    /** Keep the most recent messages, always starting with a visitor message. */
    private static function trim(array $messages): array
    {
        $messages = array_slice($messages, -(int) config('store.assistant.history_messages', 20));
        while ($messages && $messages[0]['role'] !== 'user') {
            array_shift($messages);
        }

        return array_values($messages);
    }

    private function unavailable(): JsonResponse
    {
        return response()->json([
            'message' => 'Our assistant is unavailable right now. You can request a quote at /quote or email '.setting('brand.email').'.',
        ], 503);
    }
}
