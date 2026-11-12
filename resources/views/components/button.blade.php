@aware([
    /*
     * Set only inside a ds::button-group: its size, variant and orientation.
     * A key no caller writes, so nothing outside a group can reach a button
     * this way (see View/Components/ButtonGroup.php for why that matters).
     */
    'buttonGroup' => null,
])

@props([
    /** primary | secondary | ghost | danger. Inside a ds::button-group, the group's unless set here; primary otherwise. */
    'variant' => null,
    /** sm | md | lg | fab — md is responsive: lg below the sm breakpoint, md above it. Inside a group, the group's unless set here. */
    'size' => null,
    /** Render as a link. Anything that navigates should be an `a` element, not a button. */
    'href' => null,
    /** Square, no label. Requires an accessible name via aria-label or title. */
    'iconOnly' => false,
    /**
     * Shows a spinner and blocks re-clicks (a second submit while the first is
     * in flight is the bug this state exists to prevent). It stays at full
     * contrast: fading it would make "working" look like "disabled", and those
     * mean opposite things — one is temporary, the other is a refusal.
     */
    'loading' => false,
    'disabled' => false,
    /** Pill shape. The mobile FAB is this component, not a separate one. */
    'pill' => false,
    /**
     * neutral | danger — for `ghost` and `secondary` only.
     *
     * A quiet destructive control that sits beside an ordinary one ("Remove
     * photo" next to "Save photo"), where a filled red button would shout over
     * the action the form is for. `primary` and `danger` ignore it: one is
     * the page's action and the other is already red.
     */
    'tone' => 'neutral',
])

@php
    $group = is_array($buttonGroup) ? $buttonGroup : null;
    $variant ??= $group['variant'] ?? 'primary';
    $size ??= $group['size'] ?? 'md';

    // Padding is calc(step - 1px) so the always-present 1px border is absorbed
    // and a `primary` sits exactly where a `secondary` would. Removing the calc
    // makes filled and outlined buttons disagree by 2px.
    $sizes = [
        'sm' => $iconOnly
            ? 'size-control-sm'
            : 'h-control-sm px-[calc(--spacing(2.5)-1px)] text-meta',
        // The default is one component at two breakpoints — that is what gets a
        // 44px touch target on phones without a separate mobile button.
        'md' => $iconOnly
            ? 'size-control-lg sm:size-control-md'
            : 'h-control-lg px-[calc(--spacing(3.5)-1px)] text-body-touch '
              .'sm:h-control-md sm:px-[calc(--spacing(3)-1px)] sm:text-body',
        'lg' => $iconOnly
            ? 'size-control-lg'
            : 'h-control-lg px-[calc(--spacing(3.5)-1px)] text-body-touch',
        // The mobile FAB. Always icon-only and always a circle in practice, but
        // it is this component rather than a separate one — same fill, same
        // hover, same focus ring.
        'fab' => 'size-control-fab',
    ];

    $variants = [
        'primary' => 'border-transparent bg-surface-inverse text-on-inverse '
            .'hover:bg-surface-inverse-hover',
        'secondary' => 'border-border bg-surface text-fg shadow-raised '
            .'hover:bg-surface-subtle hover:border-fg/20',
        'ghost' => 'border-transparent bg-transparent text-fg-body '
            .'hover:bg-fg/5 hover:text-fg',
        'danger' => 'border-transparent bg-danger text-on-danger hover:bg-danger-hover',
    ];

    // The hovered label darkens to on-danger-tint, as the alert's does:
    // `danger` on the wash is 4.36:1, under AA in both themes.
    $dangerTones = [
        'secondary' => 'border-border bg-surface text-danger shadow-raised '
            .'hover:bg-danger-wash hover:text-on-danger-tint',
        'ghost' => 'border-transparent bg-transparent text-danger '
            .'hover:bg-danger-wash hover:text-on-danger-tint',
    ];

    $variantClasses = $tone === 'danger' && isset($dangerTones[$variant])
        ? $dangerTones[$variant]
        : ($variants[$variant] ?? $variants['primary']);

    $classes = implode(' ', [
        // `isolate` keeps the focus ring from being clipped by a parent stacking
        // context, which is why it is on the base and not the focus state.
        'relative isolate inline-flex items-center justify-center gap-2',
        /*
         * In a group the corners come from the group: custom properties it sets
         * on each of its children, zero where this button meets a neighbour. A
         * pill has no place in a joined row. Hovered and focused buttons rise
         * above their neighbours, so the border they share, and the ring, are
         * drawn whole.
         */
        match (true) {
            $group === null => $pill ? 'rounded-full' : 'rounded-control',
            $group['orientation'] === 'vertical' => 'rounded-t-(--ds-join-start) rounded-b-(--ds-join-end) hover:z-10 focus-visible:z-10',
            default => 'rounded-s-(--ds-join-start) rounded-e-(--ds-join-end) hover:z-10 focus-visible:z-10',
        },
        'border font-semibold whitespace-nowrap motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
        /*
         * focus-visible only: a mouse click should not leave a ring behind, and
         * browsers draw their own ring on :focus-visible alone, so nothing has
         * to hide one on :focus. This carried `focus:outline-hidden` until 2.0,
         * which in Tailwind v4 sets the outline STYLE to none on every focus,
         * focus-visible included, and the 2px focus-ring outline below it was
         * never drawn: no keyboard user saw where they were.
         */
        'focus-visible:outline-2 focus-visible:outline-offset-2',
        'focus-visible:outline-focus-ring',
        // A loading button carries the disabled attribute to stop double
        // submits, so the disabled: styles would otherwise fade it too.
        $loading ? 'cursor-wait' : 'disabled:opacity-50 disabled:cursor-not-allowed',
        $sizes[$size] ?? $sizes['md'],
        $variantClasses,
    ]);

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'class' => $classes,
        // A bare `button` element defaults to type=submit, which is how a "Cancel"
        // button ends up saving the form it sits in.
        'type' => $href ? null : 'button',
        'href' => $href,
    ])->filter(fn ($v) => $v !== null) }}
    @if ($disabled || $loading)
        @if (! $href) disabled @endif
        aria-disabled="true"
    @endif
    @if ($loading) aria-busy="true" @endif
>
    @if ($loading)
        {{-- Replaces the leading icon rather than the label, so the button keeps
             its width and does not jump while it works. --}}
        <svg class="size-4 shrink-0 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
            <path d="M12 3a9 9 0 1 0 9 9" />
        </svg>
    @endif
    {{ $slot }}
</{{ $tag }}>
