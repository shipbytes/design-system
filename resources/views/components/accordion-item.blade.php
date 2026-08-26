@props([
    /** The section heading. Always visible. */
    'title',
    /**
     * Stable key for this section, used by the parent to track what is open.
     * Derived from the title when omitted — pass one when two sections share a
     * title, or when the title comes from data that changes.
     */
    'name' => null,
    /**
     * Heading level for the button's wrapper. A disclosure's trigger must sit in
     * a heading so a screen reader can jump between sections; which level is
     * correct depends on the page, and only the page knows.
     */
    'as' => 'h3',
])

{{--
    @aware reads the PARENT accordion's `open` prop, so the item can render its
    own initial state server-side. Without it every panel renders collapsed and
    the section that is supposed to be open only opens once Alpine boots — a
    visible jump, and the wrong markup entirely for anything that never runs the
    JS.
--}}
@aware(['open' => null])

@php
    $key = $name ?? substr(md5($title), 0, 8);

    $startsOpen = is_array($open)
        ? in_array($key, $open, true)
        : $open === $key;
    $id = 'ds-acc-'.$key;

    $keyJs = json_encode($key);

    $tag = in_array($as, ['h2', 'h3', 'h4', 'h5', 'h6'], true) ? $as : 'h3';

    // Bound for what moves, rendered for what does not — the same rule the
    // select follows. See specs/select.md.
    $panelState = $startsOpen ? 'grid-rows-[1fr] visible' : 'grid-rows-[0fr] invisible';
@endphp

<div x-data="{ key: {{ $keyJs }} }">
    <{{ $tag }} class="flex">
        <button
            type="button"
            id="{{ $id }}-trigger"
            @click="toggle(key)"
            :aria-expanded="isOpen(key) ? 'true' : 'false'"
            aria-expanded="{{ $startsOpen ? 'true' : 'false' }}"
            aria-controls="{{ $id }}-panel"
            {{ $attributes->merge([
                'class' => 'flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left '
                    .'text-body font-medium text-fg transition-colors hover:bg-surface-subtle '
                    .'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring',
            ]) }}
        >
            <span class="min-w-0">{{ $title }}</span>

            <x-ds::icon
                name="chevron-down"
                variant="mini"
                size="5"
                class="shrink-0 text-fg-muted transition-transform {{ $startsOpen ? 'rotate-180' : '' }}"
                ::class="isOpen(key) && 'rotate-180'"
            />
        </button>
    </{{ $tag }}>

    {{--
        The panel animates with a grid row from 0fr to 1fr — pure CSS, so it
        needs no @alpinejs/collapse. `invisible` when closed does the
        accessibility half: visibility:hidden takes the content out of the tab
        order AND out of the accessibility tree in every browser, which
        overflow:hidden alone does not. A collapsed section whose links are
        still tabbable is the classic broken accordion.
    --}}
    <div
        id="{{ $id }}-panel"
        role="region"
        aria-labelledby="{{ $id }}-trigger"
        class="grid transition-all duration-200 ease-out {{ $panelState }}"
        :class="isOpen(key) ? 'grid-rows-[1fr] visible' : 'grid-rows-[0fr] invisible'"
    >
        <div class="overflow-hidden">
            <div class="px-4 pb-4 text-body text-fg-body">{{ $slot }}</div>
        </div>
    </div>
</div>
