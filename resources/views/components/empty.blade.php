@props(['title', 'icon' => 'box'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-md border border-dashed border-border-strong px-6 py-14 text-center']) }}>
    <x-icon :name="$icon" class="mb-3 h-8 w-8 text-muted"/>
    <p class="font-serif text-xl">{{ $title }}</p>
    @if ($slot->isNotEmpty())<div class="mt-2 max-w-md text-sm text-muted">{{ $slot }}</div>@endif
</div>
