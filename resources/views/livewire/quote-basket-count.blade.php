{{-- Quote list: deliberately a document/RFQ control, not a shopping cart. --}}
<a href="{{ route('quote.basket') }}" class="btn btn-primary btn-sm relative ml-2 gap-2 whitespace-nowrap" aria-label="Quote list, {{ $count }} {{ \Illuminate\Support\Str::plural('item', $count) }}">
    <x-icon name="quote" class="h-4 w-4"/>
    <span class="hidden sm:inline lg:hidden xl:inline">Quote list</span>
    <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-brass px-1.5 text-[11px] font-semibold leading-5 text-band" data-testid="quote-count">{{ $count }}</span>
</a>
