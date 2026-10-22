{{--
    Pagination.

    A view rather than a component, because Laravel renders it through
    `$paginator->links()` and passes `$paginator` and `$elements` in. Point at
    it once with Paginator::defaultView('ds::pagination') and every paginated
    screen follows.

    Replaces the framework's stock Tailwind view, which is built on `gray` and
    `blue-300` focus borders — a second neutral ramp and an off-system accent —
    and carries its own hand-written `dark:` classes that assume a palette this
    system does not use.

    `$livewire` (default false) switches every link to a button that calls the
    Livewire component's previousPage / nextPage / gotoPage, through the two
    thin `-livewire` views. A link would reload the page and lose the
    component's state, such as a live search. These are the only `wire:`
    directives in the package, and they are inert anywhere else.
--}}
@php
    $livewire ??= false;
    $tag = $livewire ? 'button' : 'a';

    // Livewire's own views scroll back up on a page change, and a host swapping
    // to these would otherwise lose that without noticing. Same variable, same
    // default, same opt-out: links(data: ['scrollTo' => false]).
    $scrollTo ??= 'body';

    // One control, two transports: a link in a plain page, a button that calls
    // the component in Livewire mode. Same classes either way.
    $go = function (string $method, ?string $url, string $key, array $args = [], ?string $rel = null) use ($livewire, $paginator, $scrollTo) {
        $pageName = $paginator->getPageName();

        return new \Illuminate\View\ComponentAttributeBag(array_filter($livewire ? [
            'type' => 'button',
            'wire:click' => $method.'('.implode(', ', [...$args, "'{$pageName}'"]).')',
            'wire:loading.attr' => 'disabled',
            'wire:key' => "ds-paginator-{$pageName}-{$key}",
            'x-on:click' => $scrollTo === false ? null
                : "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()",
        ] : ['href' => $url, 'rel' => $rel], fn ($v) => $v !== null));
    };

    // The current page is marked the way the current nav item is: a raised
    // card, never a colour. "You are here" means the same thing in both places,
    // and elevation survives dark mode where a fill would have to be redefined.
    $current = 'border-border-strong bg-surface text-fg shadow-raised';
    $link = 'border-transparent text-fg-body hover:bg-fg/5 hover:text-fg';
    $cell = 'relative isolate inline-flex items-center justify-center rounded-control '
        .'border text-section font-medium transition-colors '
        .'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
    // Page numbers are square; a 3-digit page would otherwise be wider than a
    // 1-digit one and the row would jitter as you walk through it.
    $page = $cell.' size-control-md tabular-nums';
    $arrow = $cell.' size-control-md';
    // Disabled arrows stay visible rather than disappearing: a row that loses
    // its first control shifts every other control left.
    $off = 'border-transparent text-fg-subtle cursor-not-allowed';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-4">
        {{-- Below sm: previous / next, plus where you are. The stock view omits
             the position, which leaves a phone with no way to tell page 2 from
             page 20. --}}
        <div class="flex w-full items-center justify-between gap-3 sm:hidden">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="{{ $cell }} {{ $off }} h-control-lg px-4">{!! __('pagination.previous') !!}</span>
            @else
                <{{ $tag }} {{ $go('previousPage', $paginator->previousPageUrl(), 'previous-compact', rel: 'prev') }}
                   class="{{ $cell }} border-border bg-surface text-fg shadow-raised hover:bg-surface-subtle h-control-lg px-4">{!! __('pagination.previous') !!}</{{ $tag }}>
            @endif

            <span class="text-meta text-fg-muted tabular-nums">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <{{ $tag }} {{ $go('nextPage', $paginator->nextPageUrl(), 'next-compact', rel: 'next') }}
                   class="{{ $cell }} border-border bg-surface text-fg shadow-raised hover:bg-surface-subtle h-control-lg px-4">{!! __('pagination.next') !!}</{{ $tag }}>
            @else
                <span aria-disabled="true" class="{{ $cell }} {{ $off }} h-control-lg px-4">{!! __('pagination.next') !!}</span>
            @endif
        </div>

        {{-- sm and up: the count on the left, the controls on the right. --}}
        <p class="hidden text-meta text-fg-muted sm:block">
            {!! __('Showing') !!}
            @if ($paginator->firstItem())
                <span class="font-medium text-fg tabular-nums">{{ $paginator->firstItem() }}</span>
                {!! __('to') !!}
                <span class="font-medium text-fg tabular-nums">{{ $paginator->lastItem() }}</span>
            @else
                <span class="font-medium text-fg tabular-nums">{{ $paginator->count() }}</span>
            @endif
            {!! __('of') !!}
            <span class="font-medium text-fg tabular-nums">{{ $paginator->total() }}</span>
            {!! __('results') !!}
        </p>

        <div class="hidden items-center gap-1 sm:flex">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="{{ $arrow }} {{ $off }}">
                    <x-ds::icon name="chevron-left" size="4" />
                </span>
            @else
                <{{ $tag }} {{ $go('previousPage', $paginator->previousPageUrl(), 'previous', rel: 'prev') }} aria-label="{{ __('pagination.previous') }}"
                   class="{{ $arrow }} {{ $link }}">
                    <x-ds::icon name="chevron-left" size="4" />
                </{{ $tag }}>
            @endif

            @foreach ($elements as $element)
                {{-- A string element is the "…" separator. fg-muted, not fg-subtle:
                     hidden from screen readers, but a sighted reader still needs
                     it to see that pages are missing, and fg-subtle is 2.4:1. --}}
                @if (is_string($element))
                    <span aria-hidden="true" class="px-1 text-section text-fg-muted">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $pageNumber => $url)
                        @if ($pageNumber == $paginator->currentPage())
                            <span aria-current="page" class="{{ $page }} {{ $current }}">{{ $pageNumber }}</span>
                        @else
                            <{{ $tag }} {{ $go('gotoPage', $url, "page-{$pageNumber}", [$pageNumber]) }} aria-label="{{ __('Go to page :page', ['page' => $pageNumber]) }}"
                               class="{{ $page }} {{ $link }}">{{ $pageNumber }}</{{ $tag }}>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <{{ $tag }} {{ $go('nextPage', $paginator->nextPageUrl(), 'next', rel: 'next') }} aria-label="{{ __('pagination.next') }}"
                   class="{{ $arrow }} {{ $link }}">
                    <x-ds::icon name="chevron-right" size="4" />
                </{{ $tag }}>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="{{ $arrow }} {{ $off }}">
                    <x-ds::icon name="chevron-right" size="4" />
                </span>
            @endif
        </div>
    </nav>
@endif
