@if ($paginator->hasPages())
    <nav class="pagination">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span style="opacity:.4;cursor:not-allowed;"><i class="fa-solid fa-chevron-left"></i></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"><i class="fa-solid fa-chevron-left"></i></a>
        @endif

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"><i class="fa-solid fa-chevron-right"></i></a>
        @else
            <span style="opacity:.4;cursor:not-allowed;"><i class="fa-solid fa-chevron-right"></i></span>
        @endif
    </nav>
@endif
