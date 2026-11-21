{{-- Back to top (bottom-left, stacked above the WhatsApp button, so it never collides with the
     chat panel that opens upwards on the right). Appears after about one screen of scrolling;
     hidden while the mobile menu or the phone-size chat sheet is open. --}}
<button type="button"
        x-data="{ show: false, nav: false, chat: false }"
        x-init="const update = () => show = window.scrollY > window.innerHeight * 0.9; update(); window.addEventListener('scroll', update, { passive: true })"
        x-show="show && !nav && !chat" x-cloak
        x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:leave="transition duration-150" x-transition:leave-end="opacity-0 translate-y-2"
        x-on:mobile-nav.window="nav = $event.detail.open"
        x-on:chat-panel.window="chat = $event.detail.open && window.matchMedia('(max-width: 639px)').matches"
        @click="window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' }); document.getElementById('main')?.focus({ preventScroll: true })"
        @class(['floating-btn floating-btn--left back-to-top border-border bg-surface text-text hover:-translate-y-0.5 hover:border-text', 'above-whatsapp' => \App\Support\WhatsApp::configuredLink()])
        aria-label="Back to top" title="Back to top" data-testid="back-to-top">
    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
</button>
