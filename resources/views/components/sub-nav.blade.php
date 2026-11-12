@props([
    'label',
    /**
     * Whether the section starts expanded. Left out, it is open exactly when
     * one of its pages is the current one, which is the case that matters:
     * the reader lands with the page they are on already in view.
     */
    'open' => null,
    /**
     * Alpine expression that is true when the rail is collapsed to icons, the
     * same expression the rail's nav items take. Collapsed, the section's
     * pages are hidden and the section becomes a link to its first page.
     */
    'collapsedWhen' => null,
    /**
     * Where the collapsed section links to. Left out, its first page.
     */
    'href' => null,
    /** Wrap the icon in a tinted chip, as nav-item does. */
    'chip' => false,
])

@php
    $children = (string) $slot;

    // Read off the rendered children rather than asked for: the pages already
    // say which one is current, and a second flag on the parent would be one
    // more thing to keep in step with them.
    $current = str_contains($children, 'aria-current="page"');
    $open = $open === null ? $current : (bool) $open;

    if ($collapsedWhen && $href === null && preg_match('/<a\b[^>]*?\shref="([^"]*)"/i', $children, $m)) {
        $href = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
    }

    if ($collapsedWhen && ($href === null || $href === '')) {
        throw new \InvalidArgumentException(
            "ds::sub-nav \"{$label}\" has nowhere to link when the rail is collapsed.\n\n"
            ."Collapsed, its pages are hidden and the section links to the first of them. "
            ."Put a ds::nav-item inside it, or give it an href."
        );
    }

    // Stable across renders, but the same for two identical sections: a rail
    // drawn twice on one page (desktop and phone) passes an id to each.
    $id = $attributes->get('id') ?? 'ds-sub-nav-'.substr(md5($label.$children), 0, 8);

    // "Contains the current page" is the label and icon at full strength and
    // nothing more. The raised card stays the one mark of "you are here"; a
    // second card, or a tint, would claim the section is the page.
    $state = $current ? 'text-fg' : 'text-fg-body hover:text-fg';
    $iconState = $current ? 'text-fg-body' : 'text-fg-subtle';

    $base = 'flex items-center rounded-control border border-transparent text-left text-section font-medium transition-colors hover:bg-fg/5 '.$state;
@endphp

{{--
    One flag, read by this component only, so it is inline rather than a
    behaviour. Everything a reader sees before Alpine boots is rendered here:
    aria-expanded, the chevron and the hidden pages all follow the PHP value.
--}}
<div x-data="{ dsSubNavOpen: @js($open) }" {{ $attributes }}>
    <button
        type="button"
        id="{{ $id }}-button"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
        :aria-expanded="dsSubNavOpen ? 'true' : 'false'"
        aria-controls="{{ $id }}-pages"
        @click="dsSubNavOpen = ! dsSubNavOpen"
        @if ($collapsedWhen) x-show="!({{ $collapsedWhen }})" @endif
        class="group {{ $base }} w-full gap-3 px-2 py-2"
    >
        @isset($icon)
            @if ($chip)
                <span class="flex size-5 shrink-0 items-center justify-center rounded-chip bg-surface-sunken text-fg-muted">{{ $icon }}</span>
            @else
                <span class="shrink-0 {{ $iconState }}">{{ $icon }}</span>
            @endif
        @endisset

        <span class="truncate">{{ $label }}</span>

        <x-ds::icon
            name="chevron-down"
            variant="mini"
            size="4"
            class="ms-auto shrink-0 text-fg-subtle group-aria-expanded:rotate-180 motion-safe:transition-transform motion-safe:duration-popover-enter motion-safe:ease-enter"
        />
    </button>

    @if ($collapsedWhen)
        {{--
            Collapsed, a disclosure has nothing to disclose: there is no room
            for the pages beside an icon. The section is a link to its first
            page instead, one press from anywhere, with no script beyond the
            rail's own.
        --}}
        <a
            href="{{ $href }}"
            title="{{ $label }}"
            x-show="{{ $collapsedWhen }}"
            x-cloak
            class="{{ $base }} size-8 justify-center p-2"
        >
            @isset($icon)
                @if ($chip)
                    <span class="flex size-5 shrink-0 items-center justify-center rounded-chip bg-surface-sunken text-fg-muted">{{ $icon }}</span>
                @else
                    <span class="shrink-0 {{ $iconState }}">{{ $icon }}</span>
                @endif
            @endisset
            <span class="sr-only">{{ $label }}</span>
        </a>
    @endif

    {{--
        The guide line sits under the centre of the parent's icon, and the
        pages' labels line up with the parent's label: the indent says
        "belongs to" without a second icon column. Measured for the default
        16px icon and the 20px chip; an icon of another size moves the label,
        not the line.
    --}}
    <div
        id="{{ $id }}-pages"
        role="group"
        aria-labelledby="{{ $id }}-button"
        x-show="dsSubNavOpen{{ $collapsedWhen ? ' && !('.$collapsedWhen.')' : '' }}"
        @style(['display: none' => ! $open])
        @class(['border-s border-divider', 'ms-4.5 ps-3.25' => $chip, 'ms-4 ps-2.75' => ! $chip])
    >
        {{ $slot }}
    </div>
</div>
