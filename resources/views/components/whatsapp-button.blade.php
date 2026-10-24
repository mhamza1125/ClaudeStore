{{-- Floating WhatsApp button (bottom-left). Configured in Admin → Settings → WhatsApp.
     Positioning lives in the .floating-btn component (resources/css/app.css). --}}
@php $whatsappLink = \App\Support\WhatsApp::configuredLink(); @endphp
@if ($whatsappLink)
    <a href="{{ $whatsappLink }}" target="_blank" rel="noopener noreferrer"
       x-data="{ chat: false, nav: false }" x-show="!chat && !nav"
       x-on:chat-panel.window="chat = $event.detail.open && window.matchMedia('(max-width: 639px)').matches"
       x-on:mobile-nav.window="nav = $event.detail.open"
       class="floating-btn floating-btn--left border-border bg-surface text-text hover:-translate-y-0.5 hover:border-accent hover:text-accent"
       aria-label="Chat with us on WhatsApp (opens in a new tab)" title="Chat with us on WhatsApp" data-testid="whatsapp-button">
        <svg viewBox="0 0 24 24" class="h-7 w-7" fill="currentColor" aria-hidden="true">
            <path d="M12.04 2a9.9 9.9 0 0 0-8.5 15l-1.4 5 5.1-1.3A9.9 9.9 0 1 0 12.04 2Zm0 18.1a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3a8.2 8.2 0 1 1 6.9 3.8Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.8-1.2.2-.6.2-1.1.1-1.2l-.5-.2Z"/>
        </svg>
    </a>
@endif
