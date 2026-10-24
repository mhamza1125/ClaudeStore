<a href="{{ route('quote.basket') }}" class="btn btn-primary btn-sm relative ml-1 whitespace-nowrap" aria-label="Quote list, {{ $count }} {{ \Illuminate\Support\Str::plural('item', $count) }}">
    <x-icon name="quote" class="h-4 w-4"/>
    <span class="hidden sm:inline lg:hidden xl:inline">Quote list</span>
    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-on-primary px-1.5 text-[11px] font-semibold text-primary" data-testid="quote-count">{{ $count }}</span>
</a>
