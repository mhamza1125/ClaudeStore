{{--
    Floating chatbot (bottom-right). Configured in Admin → Settings → Chatbot.
    Online (green) only when an AI provider is configured; otherwise it opens an
    honest "offline" panel with ways to reach the team. Messages are rendered as text
    (never HTML); see resources/js/chat.js.
--}}
@php
    $provider = app(\App\Assistant\Contracts\ChatProvider::class);
    $online = $provider->isConfigured();
    $assistantName = setting('chatbot.assistant_name') ?: $brand['name'].' assistant';
    $intro = (string) setting('chatbot.intro_message');
    $whatsapp = \App\Support\WhatsApp::configuredLink();
    $config = [
        'online' => $online,
        'intro' => $intro,
        'sendUrl' => route('assistant.send'),
        'resetUrl' => route('assistant.reset'),
        'history' => $online ? \App\Http\Controllers\AssistantController::history(request()) : [],
        'teaser' => (bool) setting('chatbot.show_teaser'),
        'teaserDelay' => (int) setting('chatbot.teaser_delay_seconds', 5),
        'maxLength' => (int) config('store.assistant.max_message_length', 1000),
    ];
@endphp
@if (setting('chatbot.enabled'))
    <div x-data="chatWidget(@js($config))" x-on:keydown.escape.window="open && close()" data-testid="chat-widget"
         class="fixed bottom-[max(1rem,env(safe-area-inset-bottom))] right-4 z-40 flex flex-col items-end sm:bottom-6 sm:right-6">

        {{-- Teaser bubble with the introductory message --}}
        <div x-show="showTeaser && !open" x-cloak x-transition.opacity.duration.300ms
             class="relative mb-3 w-72 max-w-[calc(100vw-6.5rem)] rounded-md border border-border bg-surface p-4 pr-9 text-sm shadow-lg" role="status">
            <button type="button" @click="dismissTeaser()" class="absolute right-2 top-2 text-muted hover:text-text" aria-label="Dismiss message"><x-icon name="x" class="h-4 w-4"/></button>
            <p class="font-medium">{{ $assistantName }}</p>
            <p class="mt-1 text-muted">{{ $intro }}</p>
            <button type="button" @click="toggle()" class="link mt-2 text-xs text-accent">{{ $online ? 'Ask a question' : 'Contact us' }}</button>
        </div>

        {{-- Panel --}}
        <section id="chat-panel" x-show="open" x-cloak x-transition.origin.bottom.right x-trap.noscroll.inert="open && window.innerWidth < 640"
                 class="fixed inset-x-3 bottom-[max(0.75rem,env(safe-area-inset-bottom))] flex max-h-[min(85vh,640px)] flex-col overflow-hidden rounded-md border border-border bg-surface shadow-2xl sm:static sm:mb-3 sm:w-96"
                 role="dialog" aria-modal="false" aria-labelledby="chat-title">
            <header class="flex items-center justify-between gap-3 border-b border-border bg-elevated px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-9 w-9 items-center justify-center rounded-full border border-accent font-serif text-accent" aria-hidden="true">AH
                        <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-elevated {{ $online ? 'bg-success' : 'bg-border-strong' }}"></span>
                    </span>
                    <div>
                        <h2 id="chat-title" class="font-sans text-sm font-semibold tracking-normal">{{ $assistantName }}</h2>
                        <p class="text-xs text-muted">{{ $online ? 'Online · AI assistant' : 'Offline' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    @if ($online)
                        <button type="button" @click="reset()" x-show="messages.length" class="btn btn-ghost btn-sm !px-2 text-xs" title="Start a new conversation">New chat</button>
                    @endif
                    <button type="button" @click="close()" class="btn btn-ghost btn-sm !px-2" aria-label="Close chat"><x-icon name="x" class="h-4 w-4"/></button>
                </div>
            </header>

            <div x-ref="log" class="flex-1 space-y-3 overflow-y-auto px-4 py-4 text-sm" role="log" aria-live="polite" aria-relevant="additions">
                <div class="mr-8 rounded-md bg-elevated px-3 py-2">{{ $intro }}</div>
                @if ($online)
                    <template x-for="(m, i) in messages" :key="i">
                        <div :class="m.role === 'user' ? 'ml-8 bg-primary text-on-primary' : 'mr-8 bg-elevated'" class="whitespace-pre-line break-words rounded-md px-3 py-2"
                             x-effect="render($el, m.content)"></div>
                    </template>
                    <div x-show="sending" class="mr-8 inline-flex items-center gap-1 rounded-md bg-elevated px-3 py-2 text-muted" aria-label="Assistant is typing">
                        <span class="animate-pulse">•</span><span class="animate-pulse [animation-delay:150ms]">•</span><span class="animate-pulse [animation-delay:300ms]">•</span>
                    </div>
                    <p x-show="error" x-text="error" class="rounded-md bg-danger-soft px-3 py-2 text-danger" role="alert"></p>
                    <div x-show="!messages.length && !sending" class="flex flex-wrap gap-2 pt-1">
                        @foreach (['What is your minimum order quantity?', 'Do you offer private label or logo embossing?', 'How does requesting a quote work?'] as $suggestion)
                            <button type="button" @click="ask(@js($suggestion))" class="rounded-full border border-border-strong px-3 py-1 text-xs hover:border-accent hover:text-accent">{{ $suggestion }}</button>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-md border border-border px-3 py-3">
                        <p class="font-medium">Our AI assistant is offline right now.</p>
                        <p class="mt-1 text-muted">Our trade team will gladly help:</p>
                        <ul class="mt-3 space-y-2">
                            <li><a href="{{ route('quote.basket') }}" class="link">Request a wholesale quote</a></li>
                            <li><a href="mailto:{{ $brand['email'] }}" class="link">Email {{ $brand['email'] }}</a></li>
                            @if ($whatsapp)<li><a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="link">Message us on WhatsApp</a></li>@endif
                        </ul>
                    </div>
                @endif
            </div>

            @if ($online)
                <form @submit.prevent="send()" class="border-t border-border p-3">
                    <div class="flex items-end gap-2">
                        <label for="chat-input" class="sr-only">Your question</label>
                        <textarea id="chat-input" x-ref="input" x-model="draft" rows="1" :maxlength="maxLength"
                                  @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); send() }"
                                  class="input max-h-32 min-h-10 resize-none" placeholder="Ask about products or MOQs…"></textarea>
                        <button type="submit" class="btn btn-primary !px-3" :disabled="sending || !draft.trim()" aria-label="Send message"><x-icon name="arrow-right" class="h-4 w-4"/></button>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-muted">
                        <span>AI-generated answers may be inaccurate. Please don't share personal or payment details.</span>
                        <a href="{{ route('quote.basket') }}" class="link shrink-0 text-accent">Request a quote</a>
                    </div>
                </form>
            @endif
        </section>

        {{-- Launcher --}}
        <button type="button" @click="toggle()" :aria-expanded="open" aria-controls="chat-panel" x-show="!open || window.innerWidth >= 640"
                class="relative flex h-14 items-center gap-2 rounded-full border border-primary bg-primary px-4 text-on-primary shadow-lg transition hover:-translate-y-0.5 hover:border-accent hover:bg-accent hover:text-on-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus"
                data-testid="chat-launcher">
            <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 9.5h8M8 12.5h5"/></svg>
            <span class="hidden text-sm font-medium sm:inline" x-text="open ? 'Close' : 'Ask us'">Ask us</span>
            <span class="sr-only">{{ $online ? 'Chat with our assistant (online)' : 'Contact options (assistant offline)' }}</span>
            <span class="absolute right-1 top-1 h-3 w-3 rounded-full border-2 border-primary {{ $online ? 'bg-success' : 'bg-border-strong' }}" aria-hidden="true" data-testid="chat-status-{{ $online ? 'online' : 'offline' }}"></span>
        </button>
    </div>
@endif
