@props([
    /** How far, in pixels, a drag has to travel for the row to stay open on release. */
    'threshold' => 56,
    /** A swipe most of the way across presses the first action of that side, and dispatches `swipe`. */
    'full' => false,
    /** No swiping; the actions stay in the row, after its content. */
    'disabled' => false,
    /**
     * Rows that close each other. By default the rows of the nearest list
     * (ul, ol, role=list); a name joins rows anywhere on the page.
     */
    'group' => null,
    /** The name of the button that opens the row without a swipe. */
    'moreLabel' => 'More actions',
])

@php
    /*
     * The actions are real buttons in the start and end slots, in the DOM and
     * the tab order whether or not the row is open: the sliding is only how
     * they are shown. Without the script (and when `disabled`) they sit at
     * the end of the row, after its content, in a plain flex row; with it
     * they go under the content, which slides to show them. See
     * specs/swipe-actions.md for why inline and not a details element.
     */
    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsSwipe', [
        'threshold' => (int) $threshold,
        'full' => (bool) $full,
        'disabled' => (bool) $disabled,
        'group' => $group,
    ]);
    // Under the content once the script has marked the row ready, which it
    // does not do for a disabled one.
    $behind = 'group-data-ds-ready/swipe:absolute group-data-ds-ready/swipe:inset-y-0';
@endphp

<div
    x-data="{{ $state }}"
    data-ds-swipe
    @if ($group) data-ds-swipe-group="{{ $group }}" @endif
    {{ $attributes->merge(['class' => 'group/swipe relative flex items-stretch overflow-hidden bg-surface']) }}
>
    <div
        data-ds-swipe-content
        @class([
            'flex min-w-0 flex-1 items-center bg-inherit',
            'group-data-ds-ready/swipe:relative group-data-ds-ready/swipe:z-10 group-data-ds-ready/swipe:touch-pan-y',
            'motion-safe:transition-transform motion-safe:duration-dialog-enter motion-safe:ease-enter data-dragging:transition-none',
        ])
    >
        <div class="min-w-0 flex-1">{{ $slot }}</div>
        @unless ($disabled)
            {{-- The way to the actions without a swipe; only drawn with the
                 script, which is what hides them under the content. --}}
            <button
                type="button"
                data-ds-swipe-more
                aria-label="{{ $moreLabel }}"
                aria-expanded="false"
                class="mr-2 hidden shrink-0 rounded-control p-2 text-fg-muted group-data-ds-ready/swipe:block motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
            >
                <x-ds::icon name="ellipsis-horizontal" size="5" />
            </button>
        @endunless
    </div>

    @isset($start)
        <div data-ds-swipe-actions="start" class="flex shrink-0 items-stretch {{ $behind }} group-data-ds-ready/swipe:start-0">{{ $start }}</div>
    @endisset
    @isset($end)
        <div data-ds-swipe-actions="end" class="flex shrink-0 items-stretch {{ $behind }} group-data-ds-ready/swipe:end-0">{{ $end }}</div>
    @endisset
</div>
