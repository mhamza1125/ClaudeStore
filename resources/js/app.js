import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { registerTheme } from './theme';
import { registerChat } from './chat';
import { registerSearch } from './search';
import { registerCarousels } from './carousels';

registerTheme(Alpine);
registerChat(Alpine);
registerSearch(Alpine);
registerCarousels(Alpine);

// Product gallery: thumbnails swap the main image; arrow keys navigate; click opens the zoom view.
Alpine.data('gallery', (count) => ({
    active: 0,
    count,
    zoom: false,
    go(i) { this.active = (i + this.count) % this.count; },
    open(i) { this.active = i; this.zoom = true; },
}));

// Image protection (deterrent only — screenshots and dev tools can't be blocked): no context
// menu or drag-to-desktop on product images. The real controls are server-side watermarking
// and the signed, access-checked media route.
const isProtected = (el) => el instanceof Element && (el.matches('img.protected-img') || el.closest('[data-protected]'));
document.addEventListener('contextmenu', (e) => { if (isProtected(e.target)) e.preventDefault(); });
document.addEventListener('dragstart', (e) => { if (isProtected(e.target)) e.preventDefault(); });

Livewire.start();
