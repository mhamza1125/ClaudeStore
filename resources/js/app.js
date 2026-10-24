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

// Product gallery: thumbnails swap the main image; arrow keys navigate.
Alpine.data('gallery', (count) => ({
    active: 0,
    count,
    go(i) { this.active = (i + this.count) % this.count; },
}));

Livewire.start();
