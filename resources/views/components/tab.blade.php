@props([
    /** Navigates. Its presence is what makes this a link rather than a tab. */
    'href' => null,
    'active' => false,
    /**
     * id of the ds::tab-panel this controls. Required for a real tab.
     *
     * Written without angle brackets ON PURPOSE. Blade's component-tag compiler
     * is text-level and does not know what a comment is — a tag written here is
     * compiled into a real component invocation, in the middle of the @props
     * block, and the whole file stops making sense. See CLAUDE.md.
     */
    'controls' => null,
    /** A count beside the label — "Open 12". Not a status: use a badge for that. */
    'count' => null,
    'disabled' => false,
])

@php
    $tag = $href && ! $disabled ? 'a' : 'button';

    $classes = implode(' ', [
        'relative -mb-px inline-flex shrink-0 items-center gap-2 whitespace-nowrap',
        'border-b-2 px-3 py-2.5 text-body font-medium transition-colors',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        'rounded-t-chip',
        $active
            // The underline is `fg`, not `accent`. A tab row is structure, not a
            // link — colouring the active one accent makes the INACTIVE tabs look
            // like the links, which is backwards.
            ? 'border-fg text-fg'
            : 'border-transparent text-fg-muted hover:border-border-strong hover:text-fg',
        $disabled ? 'cursor-not-allowed opacity-50 hover:border-transparent hover:text-fg-muted' : '',
    ]);
@endphp

<{{ $tag }}
    @if ($tag === 'a') href="{{ $href }}" @else type="button" @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if ($href)
        {{-- A link tab is a page you are ON, so aria-current — not aria-selected,
             which only means anything inside a tablist. --}}
        @if ($active) aria-current="page" @endif
    @else
        role="tab"
        aria-selected="{{ $active ? 'true' : 'false' }}"
        @if ($controls) aria-controls="{{ $controls }}" @endif
        {{-- Roving tabindex: only the selected tab is tabbable, and the arrow keys
             move between them. That is what role="tab" promises, and it is the
             host's job to wire the arrow keys to its own state — see specs. --}}
        tabindex="{{ $active ? '0' : '-1' }}"
    @endif
    @if ($disabled) aria-disabled="true" @endif
>
    {{ $slot }}

    @if ($count !== null)
        {{-- tabular-nums so the row does not reflow as counts change width. --}}
        <span @class([
            'rounded-full px-1.5 py-0.5 text-meta font-medium tabular-nums',
            'bg-neutral-tint text-on-neutral-tint' => ! $active,
            'bg-surface-inverse text-on-inverse' => $active,
        ])>{{ $count }}</span>
    @endif
</{{ $tag }}>
