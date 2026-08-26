@props([
    /**
     * Alpine expression holding the open state, e.g. "confirmOpen".
     * The component owns no state — the host declares it and this reads it,
     * which is what keeps the same markup usable from Blade, Livewire or Volt.
     */
    'open',
    /** Heading. Also becomes the dialog's accessible name. */
    'title' => null,
    /** Second line under the title, for the "are you sure" sentence. */
    'description' => null,
    /** sm | md | lg | xl */
    'size' => 'md',
    /**
     * Backdrop click, Escape and the close button. Turn it off for a modal the
     * reader must answer — a destructive confirm whose backdrop dismisses it
     * gets dismissed by accident, and the accident reads as "cancelled".
     */
    'dismissible' => true,
])

@php
    // A CLOSED set of sizes, so this is a lookup of literal class strings and
    // not "max-w-{$size}". Tailwind reads source TEXT: `max-w-lg` written here
    // is scannable, an interpolated one is not — the same blindness that shipped
    // every alert with a checkmark the height of its panel. A map is the fix
    // whenever the set of values is finite; only an open-ended prop (icon's
    // `size`) needs the @source inline list.
    $sizes = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
    ];

    $width = $sizes[$size] ?? $sizes['md'];

    // The visible heading IS the accessible name. aria-labelledby rather than
    // aria-label so the two cannot drift — an aria-label is a second copy of
    // the title that no one sees, and nobody updates it when the title changes.
    $id = 'ds-modal-' . substr(md5($title . $description . $open), 0, 8);

    $labelledBy = $title ? "{$id}-title" : null;
    $describedBy = $description ? "{$id}-desc" : null;

    /*
     * Focus trap, written out rather than delegated to @alpinejs/focus.
     *
     * The plugin's `x-trap` is the usual answer, but Alpine SKIPS a directive it
     * has no handler for — so in a host that forgot the plugin the trap silently
     * does not exist, and `aria-modal="true"` becomes a lie a screen reader
     * believes. This repo's whole "traps" list is failures that threw nothing;
     * this would have been the sixth. Ten lines inline depend on nothing.
     *
     * Built in @php because a multi-line expression written straight onto the
     * tag is what makes Blade give up and emit the tag as literal text.
     */
    $focusTrap = <<<'JS'
        const focusable = [...$el.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )].filter((node) => node.offsetParent !== null);
        if (!focusable.length) return;
        const at = focusable.indexOf(document.activeElement);
        const step = $event.shiftKey ? -1 : 1;
        focusable[(at + step + focusable.length) % focusable.length].focus();
    JS;

    /*
     * Open: remember what had focus, move focus into the panel, and stop the
     * page behind from scrolling — a background that scrolls under a modal is
     * the single most common way one feels broken.
     * Close: put focus back where it came from, or the reader is returned to
     * the top of the document with no idea where they were.
     */
    $focusManagement = <<<JS
        if ({$open}) {
            \$el._returnFocusTo = document.activeElement;
            document.body.classList.add('overflow-hidden');
            \$nextTick(() => {
                const panel = \$el.querySelector('[data-ds-modal-panel]');
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
    {{-- Above the bottom nav (z-40) and level with the sheet (z-50): the sheet
         is this component's mobile sibling, and the two never coexist. --}}
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div
        x-show="{{ $open }}"
        @if ($dismissible) @click="{{ $open }} = false" @endif
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        {{-- `scrim` stays dark in both themes. Deriving it from `fg` would make
             it white in dark — lighting the page up instead of pushing it back. --}}
        class="absolute inset-0 bg-scrim"
    ></div>

    <div
        x-show="{{ $open }}"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @keydown.tab.prevent="{{ $focusTrap }}"
        data-ds-modal-panel
        {{-- tabindex so the panel itself can hold focus when it contains no
             control — otherwise focus stays on the page behind and the trap has
             nothing to trap. --}}
        tabindex="-1"
        {{ $attributes->merge([
            'class' => "relative flex w-full {$width} flex-col overflow-hidden rounded-panel "
                .'bg-surface shadow-overlay outline-hidden '
                // `full`, not a viewport unit. The root is `fixed inset-0 p-4`,
                // so 100% of it is already "the viewport, less the gutter" —
                // and it stays correct when the root is NOT the viewport, which
                // is exactly what a transformed ancestor does. 100vh would also
                // be wrong on a phone: it exceeds the visible area when the URL
                // bar is showing, and the footer buttons land below the fold.
                .'max-h-full',
        ]) }}
        role="dialog"
        aria-modal="true"
        @if ($labelledBy) aria-labelledby="{{ $labelledBy }}" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
    >
        @if ($title || $dismissible)
            <div class="flex items-start justify-between gap-4 px-5 pt-5 pb-4">
                <div class="min-w-0">
                    @if ($title)
                        <h2 id="{{ $id }}-title" class="text-title text-fg">{{ $title }}</h2>
                    @endif
                    @if ($description)
                        <p id="{{ $id }}-desc" class="mt-1 text-body text-fg-muted">{{ $description }}</p>
                    @endif
                </div>

                @if ($dismissible)
                    <button
                        type="button"
                        @click="{{ $open }} = false"
                        class="-mt-1 -mr-2 shrink-0 rounded-control p-2 text-fg-muted transition-colors hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
                        aria-label="Close"
                    >
                        <x-ds::icon name="x-mark" size="5" />
                    </button>
                @endif
            </div>
        @endif

        {{-- The BODY scrolls, not the panel: the header keeps naming the dialog
             and the footer keeps its actions reachable while long content moves
             underneath. A panel that scrolls as one loses both. --}}
        {{-- Top padding only when there is no header above to provide the gap,
             bottom padding only when there is no footer below. --}}
        <div @class([
            'min-h-0 flex-1 overflow-y-auto px-5 text-body text-fg-body',
            'pt-5' => ! ($title || $dismissible),
            'pb-5' => ! isset($footer),
        ])>
            {{ $slot }}
        </div>

        @if (isset($footer))
            {{-- Actions right-aligned, primary last: the reading order of a
                 confirmation is the question, then the way out, then the
                 answer. --}}
            <div class="flex items-center justify-end gap-2 border-t border-divider px-5 py-4">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
