@props([
    /**
     * Alpine REFERENCE holding the open state, e.g. `filtersOpen` or
     * `panels.filters`.
     *
     * Must be ASSIGNABLE, not merely readable. The component does not just read
     * this — it writes to it, setting it to false from the close button, the
     * backdrop and Escape. A comparison such as `mode === 'filters'` reads
     * correctly, so the drawer OPENS, and then every dismiss path silently
     * does nothing: `mode === 'filters' = false` is not an assignment. The only
     * evidence is an "Invalid left-hand side in assignment" in the browser
     * console. OpenState::assertAssignable() below turns that into an
     * exception at render time rather than a console-only surprise.
     *
     * The component owns no state — the host declares it and this points at it,
     * which is what keeps the same markup usable from Blade, Livewire or Volt.
     */
    'open',
    /** Heading. Also becomes the dialog's accessible name. */
    'title' => null,
    /** right | left */
    'side' => 'right',
    /** sm | md | lg | xl | 2xl | full */
    'size' => 'md',
    'dismissible' => true,
])

@php
    // `open` is assigned to, not just read — see the prop docblock. A
    // non-assignable expression opens fine and then ignores every way of
    // closing, which is a console-only failure. Make it a loud one.
    \Shipbytes\BladeUi\Support\OpenState::assertAssignable($open, 'ds::drawer');

    // Closed sets, literal classes. Position, transform origin and the two
    // transition endpoints all have to agree, so they live in one map rather
    // than being assembled from a `side` string at three separate points.
    $sides = [
        'right' => [
            'position' => 'right-0 origin-right border-l',
            'from' => 'translate-x-full',
        ],
        'left' => [
            'position' => 'left-0 origin-left border-r',
            'from' => '-translate-x-full',
        ],
    ];

    $s = $sides[$side] ?? $sides['right'];

    $sizes = [
        'sm' => 'max-w-sm',     // 24rem — a filter list
        'md' => 'max-w-md',     // 28rem — the default
        'lg' => 'max-w-lg',     // 32rem — the widest the source dashboard used
        'xl' => 'max-w-xl',     // 36rem ┐ a record beside the list it came from,
        '2xl' => 'max-w-2xl',   // 42rem ┘ a detail pane with a table in it
        // NOT `max-w-none`. This container is `fixed inset-0` with no padding,
        // so `none` would run the panel edge to edge with no page visible behind
        // it — and a panel covering everything is a screen, not a drawer. The
        // sliver is what says the thing you came from is still there and one
        // click away, which is the whole difference between the two.
        'full' => 'max-w-[calc(100vw-3rem)]',
    ];

    $width = $sizes[$size] ?? $sizes['md'];

    $id = 'ds-drawer-'.substr(md5($title.$open.$side), 0, 8);
    $labelledBy = $title ? "{$id}-title" : null;

    // The modal's layer (partials/modal.php): the same trap, focus in and
    // back out and scroll lock, and its place in the overlay stack. It was
    // a copy until the stack, which only works if every overlay keeps it
    // the same way: see specs/drawer.md.
    $layer = \Shipbytes\BladeUi\Support\Modal::recipe();
    $focusTrap = $layer['trap'];
    $focusManagement = $layer['focus']($open, '[data-ds-drawer-panel]');
    $escape = $layer['escape']($open);
@endphp

<div
    x-show="{{ $open }}"
    x-cloak
    x-effect="{{ $focusManagement }}"
    @if ($dismissible)
        @keydown.escape.window="{{ $escape }}"
    @endif
    class="fixed inset-0 z-50"
>
    <div
        x-show="{{ $open }}"
        @if ($dismissible) @click="{{ $open }} = false" @endif
        x-transition:enter="motion-safe:transition motion-safe:duration-slide-enter motion-safe:ease-enter"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="motion-safe:transition motion-safe:duration-slide-leave motion-safe:ease-leave"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="{{ $layer['backdrop'] }}"
    ></div>

    <div
        x-show="{{ $open }}"
        {{-- Slower out than in, and slower than the modal: a panel travelling the
             full height of the screen at the modal's 200ms reads as a flinch. --}}
        x-transition:enter="motion-safe:transition motion-safe:duration-slide-enter motion-safe:ease-enter"
        x-transition:enter-start="{{ $s['from'] }}"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="motion-safe:transition motion-safe:duration-slide-leave motion-safe:ease-leave"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="{{ $s['from'] }}"
        @keydown.tab.prevent="{{ $focusTrap }}"
        data-ds-drawer-panel
        tabindex="-1"
        {{ $attributes->merge([
            'class' => "absolute inset-y-0 {$s['position']} flex w-full {$width} flex-col "
                .'border-border bg-surface shadow-overlay outline-hidden '.$layer['covered'],
        ]) }}
        role="dialog"
        aria-modal="true"
        @if ($labelledBy) aria-labelledby="{{ $labelledBy }}" @endif
    >
        @if ($title || $dismissible)
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-divider px-5 py-4">
                @if ($title)
                    <h2 id="{{ $id }}-title" class="text-title text-fg">{{ $title }}</h2>
                @endif

                @if ($dismissible)
                    <button
                        type="button"
                        @click="{{ $open }} = false"
                        class="-my-1 -mr-2 ml-auto shrink-0 rounded-control p-2 text-fg-muted motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                        aria-label="Close"
                    >
                        <x-ds::icon name="x-mark" size="5" />
                    </button>
                @endif
            </div>
        @endif

        {{-- The body scrolls; the header and footer stay. A drawer is usually a
             long list of filters, which is exactly when losing the Apply button
             off the bottom matters most. --}}
        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 text-body text-fg-body">
            {{ $slot }}
        </div>

        @if (isset($footer))
            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-divider px-5 py-4">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
