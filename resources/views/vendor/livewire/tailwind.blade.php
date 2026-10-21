@php $scrollTo = isset($scrollTo) ? $scrollTo : 'body'; @endphp
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4 text-sm">
        <p class="text-muted">
            Showing <span class="font-medium text-text">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-text">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-text">{{ $paginator->total() }}</span>
        </p>
        <div class="flex items-center gap-1">
            <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollTo ? "(\$el.closest('{$scrollTo}') || document.body).scrollIntoView()" : '' }}" @disabled($paginator->onFirstPage()) class="btn btn-secondary btn-sm"><x-icon name="chevron-left" class="h-4 w-4"/><span class="sr-only">Previous</span></button>
            @foreach ($elements as $element)
                @if (is_string($element))<span class="px-2 text-muted">{{ $element }}</span>@endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <button type="button" wire:key="page-{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" @if ($page == $paginator->currentPage()) aria-current="page" @endif
                            class="btn btn-sm {{ $page == $paginator->currentPage() ? 'btn-primary' : 'btn-ghost hidden sm:inline-flex' }}">{{ $page }}</button>
                    @endforeach
                @endif
            @endforeach
            <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" @disabled(! $paginator->hasMorePages()) class="btn btn-secondary btn-sm"><x-icon name="chevron-right" class="h-4 w-4"/><span class="sr-only">Next</span></button>
        </div>
    </nav>
@endif
