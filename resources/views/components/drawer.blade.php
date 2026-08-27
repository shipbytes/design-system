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
            'position' => 'right-0 border-l',
            'from' => 'translate-x-full',
        ],
        'left' => [
            'position' => 'left-0 border-r',
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

    // Identical to the modal's, and deliberately not shared: see specs/drawer.md.
    $focusTrap = <<<'JS'
        const focusable = [...$el.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )].filter((node) => node.offsetParent !== null);
        if (!focusable.length) return;
        const at = focusable.indexOf(document.activeElement);
        const step = $event.shiftKey ? -1 : 1;
        focusable[(at + step + focusable.length) % focusable.length].focus();
    JS;

    $focusManagement = <<<JS
        if ({$open}) {
            \$el._returnFocusTo = document.activeElement;
            document.body.classList.add('overflow-hidden');
            \$nextTick(() => {
                const panel = \$el.querySelector('[data-ds-drawer-panel]');
                (panel.querySelector('[autofocus]') ?? panel).focus();
            });
        } else {
            document.body.classList.remove('overflow-hidden');
            \$el._returnFocusTo?.focus();
        }
    JS;
@endphp

<div
    x-show="{{ $open }}"
    x-cloak
    x-effect="{{ $focusManagement }}"
    @if ($dismissible)
        @keydown.escape.window="if ({{ $open }}) {{ $open }} = false"
    @endif
    class="fixed inset-0 z-50"
>
    <div
        x-show="{{ $open }}"
        @if ($dismissible) @click="{{ $open }} = false" @endif
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-scrim"
    ></div>

    <div
        x-show="{{ $open }}"
        {{-- Slower out than in, and slower than the modal: a panel travelling the
             full height of the screen at the modal's 200ms reads as a flinch. --}}
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="{{ $s['from'] }}"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="{{ $s['from'] }}"
        @keydown.tab.prevent="{{ $focusTrap }}"
        data-ds-drawer-panel
        tabindex="-1"
        {{ $attributes->merge([
            'class' => "absolute inset-y-0 {$s['position']} flex w-full {$width} flex-col "
                .'border-border bg-surface shadow-overlay outline-hidden',
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
                        class="-my-1 -mr-2 ml-auto shrink-0 rounded-control p-2 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
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
