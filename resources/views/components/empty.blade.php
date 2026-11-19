@props(['title', 'icon' => 'box'])
{{-- Empty state: a stitched patch with one clear next step in the slot. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-lg border-[1.5px] border-dashed border-border-strong bg-surface/60 px-6 py-14 text-center']) }}>
    <span class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-elevated text-clay"><x-icon :name="$icon" class="h-6 w-6"/></span>
    <p class="font-serif text-2xl">{{ $title }}</p>
    @if ($slot->isNotEmpty())<div class="mt-2 max-w-md text-sm text-muted">{{ $slot }}</div>@endif
</div>
