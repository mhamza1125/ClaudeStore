{{-- Quote list: deliberately a document/RFQ control, not a shopping cart. The header's one accent-coloured element. --}}
<a href="{{ route('quote.basket') }}" class="btn btn-primary btn-sm relative ml-1.5 gap-2 whitespace-nowrap !pl-3.5 !pr-2" aria-label="Quote list, {{ $count }} {{ \Illuminate\Support\Str::plural('item', $count) }}">
    <x-icon name="quote" class="h-4 w-4"/>
    <span class="hidden sm:inline lg:hidden xl:inline">Quote list</span>
    <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-on-primary px-1.5 text-[11px] font-bold leading-6 text-primary tabular-nums" data-testid="quote-count">{{ $count }}</span>
</a>
