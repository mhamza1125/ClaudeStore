@props(['name', 'title', 'maxWidth' => 'max-w-lg'])
{{-- Open with: $dispatch('open-modal', '{{ $name }}') --}}
<div x-data="{ open: false }" x-on:open-modal.window="open = ($event.detail === '{{ $name }}')" x-on:close-modal.window="open = false"
     x-on:keydown.escape.window="open = false" x-show="open" x-cloak class="fixed inset-0 z-(--z-modal) flex items-end justify-center p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="modal-{{ $name }}-title">
    <div class="fixed inset-0 bg-overlay" @click="open = false"></div>
    <div x-show="open" x-transition x-trap.noscroll="open" class="relative w-full {{ $maxWidth }} card p-6 shadow-2xl">
        <div class="mb-4 flex items-start justify-between gap-4">
            <h2 id="modal-{{ $name }}-title" class="text-2xl">{{ $title }}</h2>
            <button type="button" class="text-muted hover:text-text" @click="open = false" aria-label="Close"><x-icon name="x"/></button>
        </div>
        {{ $slot }}
    </div>
</div>
