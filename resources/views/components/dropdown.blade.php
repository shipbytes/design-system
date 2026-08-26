@props([
    /**
     * Alpine expression holding the open state, e.g. "actionsOpen".
     *
     * OPTIONAL, unlike sheet and modal — and the difference is deliberate. Those
     * two are page-level overlays whose open state the host usually already owns
     * (a selected record, a route). A dropdown's state is local to its own
     * trigger and is never shared with anything, and a kebab menu on every row
     * of a table would otherwise need a wrapper `x-data` per row. Left out, the
     * component scopes its own; passed in, the host stays in charge.
     */
    'open' => null,
    /** bottom-start | bottom-end | top-start | top-end */
    'placement' => 'bottom-end',
])

@php
    $scoped = $open === null;
    $state = $scoped ? 'dsDropdownOpen' : $open;

    // A CLOSED set, so these are literal class strings a scanner can read rather
    // than "top-{$side}" built at runtime. See the note in modal.blade.php.
    $placements = [
        'bottom-start' => 'top-full left-0 mt-2 origin-top-left',
        'bottom-end' => 'top-full right-0 mt-2 origin-top-right',
        'top-start' => 'bottom-full left-0 mb-2 origin-bottom-left',
        'top-end' => 'bottom-full right-0 mb-2 origin-bottom-right',
    ];

    $position = $placements[$placement] ?? $placements['bottom-end'];

    // Named so it cannot collide with the `trigger` SLOT, which arrives as
    // $trigger — assigning to that name here would replace the consumer's markup
    // with this selector string.
    $triggerSelector = 'button, a, [role="button"]';

    /*
     * The trigger is the CONSUMER's element — a ds::button, usually — so the
     * component cannot put aria-expanded on it at compile time. It sets it on
     * whatever element the slot turned out to contain, and keeps it in sync.
     *
     * Without this the menu is invisible to a screen reader: nothing announces
     * that the button owns a menu, or that the menu is now open. A visual-only
     * dropdown is the accessibility equivalent of the unstyled-vendor bug —
     * it looks completely fine and is completely broken.
     */
    $syncTrigger = <<<JS
        const trigger = \$el.querySelector('{$triggerSelector}');
        if (trigger) {
            trigger.setAttribute('aria-haspopup', 'menu');
            trigger.setAttribute('aria-expanded', {$state});
        }
    JS;

    /*
     * Arrow keys move between items, Home/End jump to the ends. role="menu" is a
     * PROMISE that this works — a menu that only responds to Tab is a keyboard
     * user being told one thing and given another.
     *
     * Built in @php rather than inline: a multi-line expression on the tag is
     * what makes Blade emit the tag as literal text instead of compiling it.
     */
    $moveFocus = <<<'JS'
        const items = [...$el.querySelectorAll('[role="menuitem"]:not([aria-disabled="true"])')];
        if (!items.length) return;
        const at = items.indexOf(document.activeElement);
        const to = $event.key === 'Home' ? 0
            : $event.key === 'End' ? items.length - 1
            : $event.key === 'ArrowUp' ? (at <= 0 ? items.length - 1 : at - 1)
            : (at === items.length - 1 ? 0 : at + 1);
        items[to].focus();
    JS;

    // Focus lands on the first item when the menu opens, and goes back to the
    // trigger when it closes — otherwise dismissing a menu drops the reader at
    // the top of the document with no idea where they were.
    $focusManagement = <<<JS
        if ({$state}) {
            \$nextTick(() => \$el.querySelector('[role="menuitem"]:not([aria-disabled="true"])')?.focus());
        } else if (\$el.contains(document.activeElement)) {
            \$el.closest('[data-ds-dropdown]')?.querySelector('{$triggerSelector}')?.focus();
        }
    JS;
@endphp

<div
    @if ($scoped) x-data="{ dsDropdownOpen: false }" @endif
    data-ds-dropdown
    {{-- `h-fit` is load-bearing. The menu is anchored with `top-full`, i.e. to
         the BOTTOM OF THIS BOX — so the box has to hug the trigger. Dropped into
         a flex toolbar (which is where dropdowns actually live) the default
         `align-items: stretch` makes this root as tall as the row, and the menu
         detaches and opens from the floor of it. Nothing errors; the menu is
         simply somewhere else.

         `h-fit` and NOT `self-start`: both stop the stretch, but self-start also
         overrides the parent's own alignment, so a dropdown in an `items-center`
         toolbar jumps to the top of the row while every button beside it stays
         centred. A non-auto cross size opts out of stretching without taking
         alignment away from the host. --}}
    {{ $attributes->merge(['class' => 'relative inline-block h-fit text-left']) }}
>
    {{-- `contents` so the wrapper generates no box of its own and the trigger
         sits in the layout exactly as it would unwrapped. Clicks still bubble
         to it, which is what carries the toggle. --}}
    <div
        class="contents"
        @click="{{ $state }} = ! {{ $state }}"
        x-effect="{{ $syncTrigger }}"
    >
        {{ $trigger ?? '' }}
    </div>

    <div
        x-show="{{ $state }}"
        x-cloak
        x-effect="{{ $focusManagement }}"
        {{-- .outside rather than a full-screen invisible backdrop: a backdrop
             would swallow the first click anywhere on the page, so closing a
             menu and pressing the thing underneath would take two clicks. --}}
        @click.outside="{{ $state }} = false"
        {{-- Choosing something closes the menu. Not .prevent — the link still
             has to navigate and the submit still has to submit. --}}
        @click="{{ $state }} = false"
        @keydown.escape.stop="{{ $state }} = false"
        {{-- Tab moves on to whatever follows the trigger, so the menu must not
             be left hanging open behind it. --}}
        @keydown.tab="{{ $state }} = false"
        @keydown.arrow-down.prevent="{{ $moveFocus }}"
        @keydown.arrow-up.prevent="{{ $moveFocus }}"
        @keydown.home.prevent="{{ $moveFocus }}"
        @keydown.end.prevent="{{ $moveFocus }}"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        {{-- `float`, not `overlay`: this hovers over the page, it does not block
             it. The shadow scale says which of those a surface is doing. --}}
        class="absolute {{ $position }} z-50 min-w-52 overflow-hidden rounded-control border border-border bg-surface py-1 shadow-float"
        role="menu"
        aria-orientation="vertical"
    >
        {{ $slot }}
    </div>
</div>
