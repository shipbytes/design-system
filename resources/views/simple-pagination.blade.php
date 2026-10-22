{{--
    Simple pagination — previous / next only, for `simplePaginate()`.

    A simple paginator never runs a COUNT query, so it knows neither the total
    nor the last page. That is the whole point of it, and it is why this view
    shows no position: there is nothing truthful to show.

    `$livewire` works as in pagination.blade.php: buttons that call the
    component instead of links that reload the page.
--}}
@php
    $livewire ??= false;
    $tag = $livewire ? 'button' : 'a';
    $scrollTo ??= 'body';

    $go = function (string $method, ?string $url, string $rel) use ($livewire, $paginator, $scrollTo) {
        $pageName = $paginator->getPageName();

        return new \Illuminate\View\ComponentAttributeBag(array_filter($livewire ? [
            'type' => 'button',
            'wire:click' => "{$method}('{$pageName}')",
            'wire:loading.attr' => 'disabled',
            'wire:key' => "ds-paginator-{$pageName}-{$rel}",
            'x-on:click' => $scrollTo === false ? null
                : "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()",
        ] : ['href' => $url, 'rel' => $rel], fn ($v) => $v !== null));
    };
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
            <{{ $tag }} {{ $go('previousPage', $paginator->previousPageUrl(), 'prev') }} class="{{ $cell }} {{ $on }}">{!! __('pagination.previous') !!}</{{ $tag }}>
        @endif

        @if ($paginator->hasMorePages())
            <{{ $tag }} {{ $go('nextPage', $paginator->nextPageUrl(), 'next') }} class="{{ $cell }} {{ $on }}">{!! __('pagination.next') !!}</{{ $tag }}>
        @else
            <span aria-disabled="true" class="{{ $cell }} {{ $off }}">{!! __('pagination.next') !!}</span>
        @endif
    </nav>
@endif
