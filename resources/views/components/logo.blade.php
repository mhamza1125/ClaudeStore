@props(['compact' => false])
<a href="{{ route('home') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 text-text']) }} aria-label="{{ $brand['name'] }} — home">
    @if ($brand['logo'])
        <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" class="h-9 w-auto">
    @else
        {{-- Logo placeholder: monogram + wordmark. Replace via Admin → Settings → Logo. --}}
        <span class="flex h-9 w-9 items-center justify-center rounded-full border border-accent font-serif text-base text-accent">{{ \Illuminate\Support\Str::of($brand['name'])->explode(' ')->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}</span>
        @unless ($compact)
            <span class="whitespace-nowrap font-serif text-xl leading-none tracking-wide sm:text-2xl">{{ $brand['name'] }}</span>
        @endunless
    @endif
</a>
