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
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary btn-sm"><x-icon name="chevron-left" class="h-4 w-4"/><span class="sr-only">Previous</span></a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-muted">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="btn btn-primary btn-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-ghost btn-sm hidden sm:inline-flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary btn-sm"><x-icon name="chevron-right" class="h-4 w-4"/><span class="sr-only">Next</span></a>
            @else
                <span class="btn btn-secondary btn-sm opacity-40" aria-disabled="true"><x-icon name="chevron-right" class="h-4 w-4"/><span class="sr-only">Next</span></span>
            @endif
        </div>
    </nav>
@endif
