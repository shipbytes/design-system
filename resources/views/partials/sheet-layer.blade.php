{{--
    ds::sheet with any of its 2.0 props: snap points, drag, a keyboard
    handle, the non-modal form, and a place in the overlay stack. A partial,
    not a second component, because it is the sheet; kept out of
    sheet.blade.php so a sheet without those props compiles to exactly the
    markup it always has (SheetUnchangedTest).

    Modal (the default), it is the modal's layer (partials/modal.php): the
    trap, focus in and back out, the scroll lock, Escape for the top layer
    only, and the stack. Non-modal, it is a region over the page with none of
    that.

    Everything that moves is x-ds-sheet, an enhancement: without the script
    Alpine skips it and the sheet opens at its natural height, as in 1.x,
    with a handle that is only a picture. With it, the panel is drawn at its
    highest point and slid down to the one it shows, by a CSS transition on
    the motion tokens, not a spring.
--}}
@php
    $modal = $modal !== false;
    $dismissible = $dismissible !== false;
    $handle = $handle !== false;

    // Every point as a string the script resolves against the window: a
    // bare number is pixels.
    $points = collect(is_string($snap) ? explode(',', $snap) : (array) ($snap ?? []))
        ->map(fn ($point) => is_numeric($point) ? ((float) $point).'px' : strtolower(trim((string) $point)))
        ->filter(fn ($point) => $point === 'full' || preg_match('/^\d+(\.\d+)?(%|px)$/', $point))
        ->values()
        ->all();

    $draggable = $draggable ?? true;
    $initial = is_int($initialSnap) || (is_string($initialSnap) && ctype_digit($initialSnap))
        ? (int) $initialSnap
        : array_search(strtolower((string) $initialSnap), $points, true);
    $initial = is_int($initial) && isset($points[$initial]) ? $initial : 0;

    $label = $title ?? $attributes->get('aria-label');
    if (! $modal && ($label === null || $label === '')) {
        throw new \InvalidArgumentException(
            'ds::sheet with modal="false" is a region over the page, and a region needs a name: pass a `title` (or an aria-label).'
        );
    }

    $id = $attributes->get('id') ?? 'ds-sheet-'.substr(md5($title.$open), 0, 8);
    $layer = \Shipbytes\BladeUi\Support\Modal::recipe();
    $focusManagement = $layer['focus']($open, '[data-ds-sheet-panel]');
    $escape = $layer['escape']($open);
    $grip = $draggable ? 'touch-none' : '';

    // A button only where there is somewhere to go; otherwise the picture 1.x drew.
    $moves = $draggable && count($points) > 1;

    $options = \Illuminate\Support\Js::from([
        'open' => $open,
        'snap' => $points,
        'initial' => $initial,
        'draggable' => (bool) $draggable,
        'dismissible' => $dismissible,
        'words' => ['expanded' => 'Expanded', 'half' => 'Half', 'collapsed' => 'Collapsed'],
    ]);

    $panel = $attributes->except(['id', 'aria-label'])->merge([
        'class' => 'relative flex flex-col overflow-hidden rounded-t-sheet bg-surface shadow-overlay outline-hidden origin-top '
            .'motion-safe:transition-transform motion-safe:duration-slide-enter motion-safe:ease-enter data-dragging:transition-none '
            .$layer['covered'],
    ]);
@endphp

<div
    x-show="{{ $open }}"
    x-cloak
    @if ($modal)
        x-effect="{{ $focusManagement }}"
        @if ($dismissible) @keydown.escape.window="{{ $escape }}" @endif
        class="fixed inset-0 z-50 lg:hidden"
    @else
        class="lg:hidden"
    @endif
>
    @if ($modal)
        <div
            @if ($dismissible) @click="{{ $open }} = false" @endif
            x-show="{{ $open }}"
            x-transition:enter="motion-safe:transition motion-safe:duration-slide-enter motion-safe:ease-enter"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="motion-safe:transition motion-safe:duration-slide-leave motion-safe:ease-leave"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="{{ $layer['backdrop'] }}"
        ></div>
    @endif

    {{-- The frame slides in and out; the panel inside it moves between the
         points. Two boxes so the two transitions never share one element's
         transition list. The script lifts the frame above an on-screen
         keyboard. --}}
    <div
        x-show="{{ $open }}"
        x-transition:enter="motion-safe:transition motion-safe:duration-slide-enter motion-safe:ease-enter"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="motion-safe:transition motion-safe:duration-slide-leave motion-safe:ease-leave"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        data-ds-sheet-frame
        @class(['fixed inset-x-0 bottom-0', 'z-40' => ! $modal])
    >
        <div
            id="{{ $id }}"
            x-ds-sheet="{{ $options }}"
            data-ds-sheet-panel
            tabindex="-1"
            @if ($modal)
                role="dialog"
                aria-modal="true"
                @keydown.tab.prevent="{{ $layer['trap'] }}"
            @else
                role="region"
                @if ($dismissible) @keydown.escape="if ({{ $open }}) { $event.stopPropagation(); {{ $open }} = false }" @endif
            @endif
            aria-label="{{ $label }}"
            {{ $panel }}
            style="max-height: {{ $maxHeight }}"
        >
            @if ($handle)
                <div data-ds-sheet-grip class="flex shrink-0 justify-center pt-3 pb-2 {{ $grip }}">
                    @if ($moves)
                        {{-- Inert until the script makes it the way to move the
                             sheet without dragging: without the script there is
                             nowhere for it to go. --}}
                        <button
                            type="button"
                            data-ds-sheet-handle
                            tabindex="-1"
                            aria-hidden="true"
                            aria-label="Sheet size"
                            aria-describedby="{{ $id }}-status"
                            class="-my-2 rounded-full px-4 py-2 cursor-grab focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                        >
                            <span class="block h-1 w-10 rounded-full bg-fg/20"></span>
                        </button>
                    @else
                        <div class="h-1 w-10 rounded-full bg-fg/20" aria-hidden="true"></div>
                    @endif
                </div>
            @endif

            @if ($title || $dismissible)
                <div data-ds-sheet-grip @class(['flex shrink-0 items-center justify-between gap-4 border-b border-divider px-5 pb-4', 'pt-4' => ! $handle, $grip])>
                    @if ($title)
                        <h2 class="text-title text-fg">{{ $title }}</h2>
                    @endif
                    @if ($dismissible)
                        <button
                            type="button"
                            @click="{{ $open }} = false"
                            class="-mr-2 ml-auto rounded-control p-2 text-fg-muted motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                            aria-label="Close"
                        >
                            <x-ds::icon name="x-mark" size="5" />
                        </button>
                    @endif
                </div>
            @endif

            <div data-ds-sheet-body class="min-h-0 flex-1 overflow-y-auto overscroll-contain pt-2 pb-[max(--spacing(2),env(safe-area-inset-bottom))]">
                {{ $slot }}
            </div>

            <div id="{{ $id }}-status" data-ds-sheet-status role="status" class="sr-only"></div>
        </div>
    </div>
</div>
