{{-- Breadcrumb trail + schema.org BreadcrumbList. $items: list of [label, url]; the last item is the current page. --}}
@props(['items' => []])
@if (count($items) > 1)
    <nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'text-[13px] text-muted']) }}>
        <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
            @foreach ($items as [$label, $url])
                <li class="flex items-center gap-1.5">
                    @if ($loop->last)
                        <span class="font-medium text-text" aria-current="page">{{ $label }}</span>
                    @else
                        <a href="{{ $url }}" class="transition-colors hover:text-accent">{{ $label }}</a>
                        <x-icon name="chevron-right" class="h-3.5 w-3.5 text-border-strong"/>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($items)->values()->map(fn ($item, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1]])->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif
