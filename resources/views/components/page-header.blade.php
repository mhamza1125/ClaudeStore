@props(['title', 'eyebrow' => null])
<div {{ $attributes->merge(['class' => 'mb-8 flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        @if ($eyebrow)<p class="eyebrow mb-2.5">{{ $eyebrow }}</p>@endif
        <h1 class="text-[2rem] leading-tight sm:text-[2.5rem]" style="letter-spacing:-0.025em">{{ $title }}</h1>
    </div>
    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
</div>
