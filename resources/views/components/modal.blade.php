@props([
    /**
     * Alpine REFERENCE holding the open state, e.g. `confirmOpen` or
     * `panels.confirm`.
     *
     * Must be ASSIGNABLE, not merely readable. The component does not just read
     * this — it writes to it, setting it to false from the close button, the
     * backdrop and Escape. A comparison such as `mode === 'confirm'` reads
     * correctly, so the modal OPENS, and then every dismiss path silently
     * does nothing: `mode === 'confirm' = false` is not an assignment. The only
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
    /** Second line under the title, for the "are you sure" sentence. */
    'description' => null,
    /** sm | md | lg | xl | 2xl | 3xl | 4xl | full */
    'size' => 'md',
    /**
     * Backdrop click, Escape and the close button. Turn it off for a modal the
     * reader must answer — a destructive confirm whose backdrop dismisses it
     * gets dismissed by accident, and the accident reads as "cancelled".
     */
    'dismissible' => true,
])

@php
    // `open` is assigned to, not just read — see the prop docblock. A
    // non-assignable expression opens fine and then ignores every way of
    // closing, which is a console-only failure. Make it a loud one.
    \Shipbytes\BladeUi\Support\OpenState::assertAssignable($open, 'ds::modal');

    // A CLOSED set of sizes, so this is a lookup of literal class strings and
    // not "max-w-{$size}". Tailwind reads source TEXT: `max-w-lg` written here
    // is scannable, an interpolated one is not — the same blindness that shipped
    // every alert with a checkmark the height of its panel. A map is the fix
    // whenever the set of values is finite; only an open-ended prop (icon's
    // `size`) needs the @source inline list.
    $sizes = [
        'sm' => 'max-w-sm',     // 24rem — a confirm, a one-field form
        'md' => 'max-w-md',     // 28rem — the default
        'lg' => 'max-w-lg',     // 32rem
        'xl' => 'max-w-xl',     // 36rem — the widest the source dashboard used
        '2xl' => 'max-w-2xl',   // 42rem ┐ the sizes a dialog needs when its
        '3xl' => 'max-w-3xl',   // 48rem │ content is WIDE rather than long:
        '4xl' => 'max-w-4xl',   // 56rem ┘ a table preview, a diff, a log excerpt
        /*
         * Near-full-screen, with a ceiling.
         *
         * `w-full` inside a `fixed inset-0 p-4` root already means "the
         * container, less the gutter", so the p-4 IS the margin and `max-w-none`
         * would be enough. Measured, that gives 1408px at 1440 and **2528px on a
         * 2560 monitor** — a dialog wider than anything else in the system, with
         * a line length nobody can read. The cap only engages above a ~1568px
         * viewport; every laptop gets the identical result either way.
         *
         * A plain rem value and not `min(96rem, calc(100vw - 2rem))`, for the
         * same reason `max-h-full` is not 100dvh: `max-w` only CAPS, so `w-full`
         * still supplies "the container less the gutter" below the ceiling —
         * and it stays right when the root is not the viewport, which is exactly
         * what a transformed ancestor makes it.
         */
        'full' => 'max-w-[96rem]',
    ];

    $width = $sizes[$size] ?? $sizes['md'];

    // The visible heading IS the accessible name. aria-labelledby rather than
    // aria-label so the two cannot drift — an aria-label is a second copy of
    // the title that no one sees, and nobody updates it when the title changes.
    $id = 'ds-modal-' . substr(md5($title . $description . $open), 0, 8);

    $labelledBy = $title ? "{$id}-title" : null;
    $describedBy = $description ? "{$id}-desc" : null;

    // The layer itself, the trap, focus in and back out, the scroll lock and
    // both transitions, is the modal recipe, which ds::command-palette opens
    // in too: see resources/views/partials/modal.php.
    $layer = \Shipbytes\BladeUi\Support\Modal::recipe();
    $focusTrap = $layer['trap'];
    $focusManagement = $layer['focus']($open);
    $escape = $layer['escape']($open);
    $backdropTransition = new \Illuminate\View\ComponentAttributeBag($layer['backdropTransition']);
    $panelTransition = new \Illuminate\View\ComponentAttributeBag($layer['panelTransition']);
@endphp

<div
    x-show="{{ $open }}"
    x-cloak
    x-effect="{{ $focusManagement }}"
    @if ($dismissible)
        @keydown.escape.window="{{ $escape }}"
    @endif
    {{-- Above the bottom nav (z-40) and level with the sheet (z-50): the sheet
         is this component's mobile sibling, and the two never coexist. --}}
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div
        x-show="{{ $open }}"
        @if ($dismissible) @click="{{ $open }} = false" @endif
        {{ $backdropTransition }}
        class="{{ $layer['backdrop'] }}"
    ></div>

    <div
        x-show="{{ $open }}"
        {{ $panelTransition }}
        @keydown.tab.prevent="{{ $focusTrap }}"
        data-ds-modal-panel
        {{-- tabindex so the panel itself can hold focus when it contains no
             control — otherwise focus stays on the page behind and the trap has
             nothing to trap. --}}
        tabindex="-1"
        {{ $attributes->merge([
            'class' => "relative flex w-full {$width} flex-col overflow-hidden rounded-panel "
                .'bg-surface shadow-overlay outline-hidden '.$layer['covered'].' '
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
                        class="-mt-1 -mr-2 shrink-0 rounded-control p-2 text-fg-muted motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring"
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
