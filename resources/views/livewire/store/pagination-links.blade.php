{{--
    Catalogue pagination (audit F-05): real <a href> links that search engines can follow,
    enhanced by Livewire (wire:click.prevent) for in-place navigation. Page 1 links to the clean
    URL without ?page=1, matching its canonical.
--}}
@php
    $scrollTo = isset($scrollTo) ? $scrollTo : 'body';
    $pageUrl = fn (int $page) => rtrim($page === 1 ? preg_replace('/([?&])page=1(?=&|$)&?/', '$1', $paginator->url(1)) : $paginator->url($page), '?&');
    $scroll = $scrollTo ? "(\$el.closest('{$scrollTo}') || document.body).scrollIntoView()" : '';
@endphp
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4 text-sm">
        <p class="text-muted">
            Showing <span class="font-medium text-text">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-text">{{ $paginator->lastItem() }}</span>
            of <span class="font-medium text-text">{{ $paginator->total() }}</span>
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-secondary btn-sm opacity-40" aria-disabled="true"><x-icon name="chevron-left" class="h-4 w-4"/><span class="sr-only">Previous</span></span>
            @else
                <a href="{{ $pageUrl($paginator->currentPage() - 1) }}" rel="prev" wire:click.prevent="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scroll }}" class="btn btn-secondary btn-sm"><x-icon name="chevron-left" class="h-4 w-4"/><span class="sr-only">Previous</span></a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))<span class="px-2 text-muted">{{ $element }}</span>@endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="btn btn-sm btn-primary">{{ $page }}</span>
                        @else
                            <a href="{{ $pageUrl($page) }}" wire:key="page-{{ $page }}" wire:click.prevent="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scroll }}"
                               class="btn btn-sm btn-ghost hidden sm:inline-flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $pageUrl($paginator->currentPage() + 1) }}" rel="next" wire:click.prevent="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scroll }}" class="btn btn-secondary btn-sm"><x-icon name="chevron-right" class="h-4 w-4"/><span class="sr-only">Next</span></a>
            @else
                <span class="btn btn-secondary btn-sm opacity-40" aria-disabled="true"><x-icon name="chevron-right" class="h-4 w-4"/><span class="sr-only">Next</span></span>
            @endif
        </div>
    </nav>
@endif
