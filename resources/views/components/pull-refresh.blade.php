@props([
    /** How far, in pixels, the content has to come down before letting go refreshes. */
    'threshold' => 72,
    /** Milliseconds after which a refresh nobody answered gives up. */
    'timeout' => 10000,
    /** Render it refreshing: busy, the spinner showing. A re-render without it ends a refresh. */
    'refreshing' => false,
    /** Where the Refresh button goes without the script: a link. Without it the button submits its form. */
    'href' => null,
    /** The Refresh button's words; false for none (then give the reader another way, the gesture is touch only). */
    'button' => 'Refresh',
    /** The region's name. */
    'label' => null,
    /** Said, politely, when a refresh ends. */
    'updatedLabel' => 'Updated',
])

@php
    /*
     * The root is the scrolling box when it is given a height (class="h-96"),
     * and the page scrolls it otherwise; the gesture reads whichever is the
     * one at the top. overscroll-behavior-y: contain keeps the browser's own
     * pull to refresh and scroll chaining out of a box that scrolls; the
     * page's own is the browser's (see docs/components/pull-refresh.md).
     */
    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsPullRefresh', [
        'threshold' => max(24, (int) $threshold),
        'timeout' => max(0, (int) $timeout),
        'updated' => $updatedLabel,
    ]);
    $control = 'inline-flex items-center gap-1.5 rounded-control px-2.5 py-1.5 text-meta font-medium text-fg-muted '
        .'motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle hover:text-fg '
        .'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring';
    $tag = $href ? 'a' : 'button';
@endphp

<div
    x-data="{{ $state }}"
    data-ds-pull
    @if ($label) role="region" aria-label="{{ $label }}" @endif
    @if ($refreshing) data-refreshing aria-busy="true" @else aria-busy="false" @endif
    {{ $attributes->merge(['class' => 'group/pull relative overflow-y-auto overscroll-y-contain']) }}
>
    {{-- The ring fills to the threshold as the content comes down, then
         spins until the refresh ends. Both are ds::progress, hidden from
         assistive technology: the region's aria-busy and the status line
         say what they show. --}}
    <div
        data-ds-pull-indicator
        aria-hidden="true"
        class="pointer-events-none absolute inset-x-0 top-0 z-10 flex h-0 justify-center"
    >
        <span class="mt-3 flex size-9 items-center justify-center rounded-full bg-surface opacity-0 shadow-raised group-data-pulling/pull:opacity-100 group-data-refreshing/pull:opacity-100">
            <x-ds::progress data-ds-pull-ring variant="circle" size="sm" tone="accent" :value="0" label="Pull to refresh" :hideLabel="true" aria-hidden="true" class="group-data-refreshing/pull:hidden" />
            <x-ds::progress variant="circle" size="sm" tone="accent" label="Refreshing" :hideLabel="true" aria-hidden="true" class="hidden group-data-refreshing/pull:inline-flex" />
        </span>
    </div>

    @if ($button !== false)
        <div class="flex justify-end px-2 pt-2">
            <{{ $tag }} @if ($href) href="{{ $href }}" @else type="submit" @endif data-ds-pull-button class="{{ $control }}">
                <x-ds::icon name="arrow-path" size="4" class="group-data-refreshing/pull:motion-safe:animate-spin" />
                {{ $button }}
            </{{ $tag }}>
        </div>
    @endif

    <div data-ds-pull-content class="motion-safe:transition-transform motion-safe:duration-dialog-enter motion-safe:ease-enter data-dragging:transition-none">
        {{ $slot }}
    </div>

    <div data-ds-pull-status role="status" class="sr-only"></div>
</div>
