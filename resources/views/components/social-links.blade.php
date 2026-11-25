{{-- Social profile icons (Admin → Settings → Social profiles). Renders nothing when no profile is visible.
     tone:   "dark" for the footer band, "light" for cream pages.
     layout: "icons" (a row of round icons) or "list" (stacked rows: icon + network name). --}}
@props(['tone' => 'light', 'size' => 'h-10 w-10', 'layout' => 'icons'])
@php $profiles = \App\Support\Social::visible(); @endphp
@if ($profiles)
    @if ($layout === 'list')
        <ul {{ $attributes->merge(['class' => 'space-y-2.5 text-sm']) }} role="list" data-testid="social-links">
            @foreach ($profiles as $p)
                <li>
                    <a href="{{ $p['url'] }}" @if ($p['url'] !== '#') target="_blank" rel="noopener me" @endif
                       aria-label="{{ $brand['name'] }} on {{ $p['label'] }}"
                       @class(['group inline-flex items-center gap-2.5 transition-colors', 'text-on-band/75 hover:text-on-band' => $tone === 'dark', 'text-text hover:text-accent' => $tone !== 'dark'])>
                        <span @class([
                            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full border transition-colors duration-200',
                            'border-on-band/15 bg-band-2 text-on-band/80 group-hover:border-tan group-hover:bg-tan group-hover:text-text' => $tone === 'dark',
                            'border-border-strong bg-surface group-hover:border-primary group-hover:bg-primary group-hover:text-on-primary' => $tone !== 'dark',
                        ])>
                            <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="currentColor" aria-hidden="true"><path d="{{ $p['icon'] }}"/></svg>
                        </span>
                        {{ $p['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <ul {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }} role="list" data-testid="social-links">
            @foreach ($profiles as $p)
                <li>
                    <a href="{{ $p['url'] }}" @if ($p['url'] !== '#') target="_blank" rel="noopener me" @endif
                       aria-label="{{ $brand['name'] }} on {{ $p['label'] }}" title="{{ $p['label'] }}"
                       @class([
                           'flex items-center justify-center rounded-full border transition duration-200 hover:-translate-y-0.5 '.$size,
                           'border-on-band/15 bg-band text-on-band/80 hover:border-tan hover:bg-tan hover:text-text' => $tone === 'dark',
                           'border-border-strong bg-surface text-text hover:border-primary hover:bg-primary hover:text-on-primary' => $tone !== 'dark',
                       ])>
                        <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="currentColor" aria-hidden="true"><path d="{{ $p['icon'] }}"/></svg>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
@endif
