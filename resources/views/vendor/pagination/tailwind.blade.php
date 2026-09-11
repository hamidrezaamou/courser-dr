@if ($paginator->hasPages())
    <nav class="pager-nav" role="navigation" aria-label="صفحه‌بندی">
        <div class="pager-nav__mobile">
            @if ($paginator->onFirstPage())
                <span class="pager-nav__btn is-disabled">قبلی</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pager-nav__btn">قبلی</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pager-nav__btn">بعدی</a>
            @else
                <span class="pager-nav__btn is-disabled">بعدی</span>
            @endif
        </div>

        <div class="pager-nav__desktop">
            <span class="pager-nav__group">
                @if ($paginator->onFirstPage())
                    <span class="pager-nav__btn is-disabled" aria-disabled="true">‹</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="pager-nav__btn" rel="prev" aria-label="صفحه قبل">‹</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="pager-nav__btn is-disabled">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="pager-nav__btn is-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="pager-nav__btn">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="pager-nav__btn" rel="next" aria-label="صفحه بعد">›</a>
                @else
                    <span class="pager-nav__btn is-disabled" aria-disabled="true">›</span>
                @endif
            </span>
        </div>
    </nav>
@endif
