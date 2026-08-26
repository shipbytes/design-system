@props([
    /** primary | secondary | ghost | danger */
    'variant' => 'primary',
    /** sm | md | lg | fab — md is responsive: lg below the sm breakpoint, md above it */
    'size' => 'md',
    /** Render as a link. Anything that navigates should be an <a>, not a button. */
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
])

@php
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

    $classes = implode(' ', [
        // `isolate` keeps the focus ring from being clipped by a parent stacking
        // context, which is why it is on the base and not the focus state.
        'relative isolate inline-flex items-center justify-center gap-2',
        $pill ? 'rounded-full' : 'rounded-control',
        'border font-semibold whitespace-nowrap transition-colors',
        // focus-visible only: a mouse click should not leave a ring behind.
        'focus:outline-hidden focus-visible:outline-2 focus-visible:outline-offset-2',
        'focus-visible:outline-focus-ring',
        // A loading button carries the disabled attribute to stop double
        // submits, so the disabled: styles would otherwise fade it too.
        $loading ? 'cursor-wait' : 'disabled:opacity-50 disabled:cursor-not-allowed',
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['primary'],
    ]);

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'class' => $classes,
        // A bare <button> defaults to type=submit, which is how a "Cancel"
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
        <svg class="size-4 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
            <path d="M12 3a9 9 0 1 0 9 9" />
        </svg>
    @endif
    {{ $slot }}
</{{ $tag }}>
