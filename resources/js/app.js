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

// Gentle section reveal: elements with .reveal fade up once as they scroll into view. Without
// IntersectionObserver (or with reduced motion, handled in CSS) they are simply visible.
function initReveal() {
    const els = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) { els.forEach((el) => el.classList.add('is-in')); return; }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } });
    }, { rootMargin: '0px 0px -8% 0px' });
    els.forEach((el) => io.observe(el));
}
document.addEventListener('DOMContentLoaded', initReveal);

Livewire.start();
