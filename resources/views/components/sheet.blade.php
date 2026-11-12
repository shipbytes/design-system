@props([
    /**
     * Alpine REFERENCE holding the open state, e.g. `moreSheetOpen` or
     * `panels.more`.
     *
     * Must be ASSIGNABLE, not merely readable. The component does not just read
     * this — it writes to it, setting it to false from the close button and the
     * backdrop. A comparison such as `mode === 'more'` reads correctly, so the
     * sheet OPENS, and then every dismiss path silently does nothing:
     * `mode === 'more' = false` is not an assignment. The only evidence is an
     * "Invalid left-hand side in assignment" in the browser console.
     * OpenState::assertAssignable() below turns that into an exception at
     * render time rather than a console-only surprise.
     *
     * The component owns no state — the host declares it and this points at it,
     * which is what keeps the same markup usable from Blade, Livewire or Volt.
     */
    'open',
    'title' => null,
    /** Cap on the panel height. The list inside scrolls; the page behind does not. */
    'maxHeight' => '85vh',
    /*
     * The props below are 2.0's. Passing ANY of them draws the sheet from
     * partials/sheet-layer.blade.php; passing none draws exactly the 1.x
     * markup below, byte for byte (SheetUnchangedTest).
     */
    /**
     * Snap points, lowest to highest or in any order: percentages of the
     * window's height ('25%'), pixels (320 or '320px'), and 'full', which is
     * `maxHeight`. The sheet is drawn at the highest and slid down to show
     * the others. Null: one point, the sheet's natural height.
     */
    'snap' => null,
    /** Where it opens: an index into `snap`, or one of its values. The first by default. */
    'initialSnap' => null,
    /** Drag the handle and header (and, below the top point, the content, by touch). True by default. */
    'draggable' => null,
    /** The backdrop, the ✕, Escape and a drag down past the lowest point close it. True by default. */
    'dismissible' => null,
    /** Draw the grab handle. True by default; a button when there are points to move between. */
    'handle' => null,
    /**
     * False: the page behind stays usable (a map under a list). No backdrop,
     * no focus trap, no scroll lock; a region named by `title`, not a dialog.
     */
    'modal' => null,
])

@php
    // `open` is assigned to, not just read — see the prop docblock. A
    // non-assignable expression opens fine and then ignores every way of
    // closing, which is a console-only failure. Make it a loud one.
    \Shipbytes\BladeUi\Support\OpenState::assertAssignable($open, 'ds::sheet');
    $layered = $snap !== null || $initialSnap !== null || $draggable !== null
        || $dismissible !== null || $handle !== null || $modal !== null;
@endphp

@if ($layered)
    @include('ds::partials.sheet-layer')
@else
{{--
    The mobile form of a modal or a drawer. It stops short of the top on purpose:
    leaving the page visible behind it is what makes a sheet feel dismissible,
    where a full-height takeover reads as a new screen.
--}}
<div x-show="{{ $open }}" x-cloak class="fixed inset-0 z-50 lg:hidden">
    <div
        @click="{{ $open }} = false"
        x-show="{{ $open }}"
        x-transition:enter="motion-safe:transition motion-safe:duration-slide-enter motion-safe:ease-enter"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="motion-safe:transition motion-safe:duration-slide-leave motion-safe:ease-leave"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-scrim"
    ></div>

    <div
        x-show="{{ $open }}"
        x-transition:enter="motion-safe:transition motion-safe:duration-slide-enter motion-safe:ease-enter"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="motion-safe:transition motion-safe:duration-slide-leave motion-safe:ease-leave"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        {{ $attributes->merge(['class' => 'fixed inset-x-0 bottom-0 overflow-hidden rounded-t-sheet bg-surface shadow-overlay']) }}
        style="max-height: {{ $maxHeight }}"
        role="dialog"
        aria-modal="true"
        @if ($title) aria-label="{{ $title }}" @endif
    >
        {{-- Drag handle. Decorative: it signals the sheet can be pushed away,
             but the backdrop and the close button are what actually do it. --}}
        <div class="flex justify-center pt-3 pb-2">
            <div class="h-1 w-10 rounded-full bg-fg/20" aria-hidden="true"></div>
        </div>

        @if ($title)
            <div class="flex items-center justify-between border-b border-divider px-5 pb-4">
                <h2 class="text-title text-fg">{{ $title }}</h2>
                <button
                    type="button"
                    @click="{{ $open }} = false"
                    class="-mr-2 rounded-control p-2 text-fg-muted motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                    aria-label="Close"
                >
                    <x-ds::icon name="x-mark" size="5" />
                </button>
            </div>
        @endif

        <div class="overflow-y-auto py-2" style="max-height: calc({{ $maxHeight }} - 100px)">
            {{ $slot }}
        </div>
    </div>
</div>
@endif
