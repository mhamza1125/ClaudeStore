@props(['title', 'eyebrow' => null])
<div {{ $attributes->merge(['class' => 'mb-8 flex flex-wrap items-end justify-between gap-4']) }}>
    <div>
        @if ($eyebrow)<p class="eyebrow mb-2">{{ $eyebrow }}</p>@endif
        <h1 class="text-3xl sm:text-4xl">{{ $title }}</h1>
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
