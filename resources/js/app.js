import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { registerTheme } from './theme';

registerTheme(Alpine);

// Product gallery: thumbnails swap the main image; arrow keys navigate.
Alpine.data('gallery', (count) => ({
    active: 0,
    count,
    go(i) { this.active = (i + this.count) % this.count; },
}));

// Homepage hero carousel. Autoplay pauses on hover/focus, in hidden tabs and when the
// visitor prefers reduced motion; `seconds` = 0 disables autoplay.
Alpine.data('heroCarousel', (count, seconds) => ({
    active: 0,
    count,
    interval: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : seconds,
    hover: false,
    focused: false,
    paused: false,
    timer: null,
    init() {
        if (this.interval > 0 && this.count > 1) {
            this.timer = setInterval(() => {
                if (!this.hover && !this.focused && !this.paused && !document.hidden) this.next();
            }, this.interval * 1000);
        }
    },
    destroy() { clearInterval(this.timer); },
    go(i) { this.active = (i + this.count) % this.count; },
    next() { this.go(this.active + 1); },
    prev() { this.go(this.active - 1); },
    toggle() { this.paused = !this.paused; },
}));

Livewire.start();
