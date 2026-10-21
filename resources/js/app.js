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

Livewire.start();
