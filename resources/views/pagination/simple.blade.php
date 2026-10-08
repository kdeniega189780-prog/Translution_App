@if ($paginator->total() > 0)
    @php
        $window = \Illuminate\Pagination\UrlWindow::make($paginator->onEachSide(1));
        $elements = array_filter([$window['first'], is_array($window['slider']) ? '…' : null, $window['slider'], is_array($window['last']) ? '…' : null, $window['last']]);
    @endphp
    <nav class="pagination" aria-label="{{ $label ?? 'Records' }} pages">
        <span class="pagination-summary">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ number_format($paginator->total()) }} <small>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</small></span>
        <div class="pagination-links">
            @if ($paginator->onFirstPage())
                <span class="page-link page-direction disabled" aria-disabled="true">Previous</span>
            @else
                <a class="page-link page-direction" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous {{ strtolower($label ?? 'records') }} page">Previous</a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="page-gap" aria-hidden="true">{{ $element }}</span>
                @else
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="page-link current" aria-current="page" aria-label="Page {{ $page }}, current page">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a class="page-link page-direction" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next {{ strtolower($label ?? 'records') }} page">Next</a>
            @else
                <span class="page-link page-direction disabled" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
