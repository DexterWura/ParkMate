@if ($paginator->hasPages())
    <nav class="ol-pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="ol-page is-disabled" aria-disabled="true">Prev</span>
        @else
            <a class="ol-page" href="{{ $paginator->previousPageUrl() }}" rel="prev">Prev</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="ol-page is-disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="ol-page is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="ol-page" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="ol-page" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span class="ol-page is-disabled" aria-disabled="true">Next</span>
        @endif
    </nav>
@endif
