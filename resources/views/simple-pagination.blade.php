{{--
    Simple pagination — previous / next only, for `simplePaginate()`.

    A simple paginator never runs a COUNT query, so it knows neither the total
    nor the last page. That is the whole point of it, and it is why this view
    shows no position: there is nothing truthful to show.
--}}
@php
    $cell = 'relative isolate inline-flex items-center justify-center rounded-control border '
        .'h-control-lg px-4 text-section font-medium transition-colors sm:h-control-md '
        .'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
    $on = 'border-border bg-surface text-fg shadow-raised hover:bg-surface-subtle';
    $off = 'border-transparent text-fg-subtle cursor-not-allowed';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center gap-3">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="{{ $cell }} {{ $off }}">{!! __('pagination.previous') !!}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $cell }} {{ $on }}">{!! __('pagination.previous') !!}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $cell }} {{ $on }}">{!! __('pagination.next') !!}</a>
        @else
            <span aria-disabled="true" class="{{ $cell }} {{ $off }}">{!! __('pagination.next') !!}</span>
        @endif
    </nav>
@endif
