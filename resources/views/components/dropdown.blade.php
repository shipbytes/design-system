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
    //
    // These are where the menu sits without the package's script. With it,
    // x-ds-anchor writes inline style over them, flipping the menu to the
    // other side when this one has no room, and the origin follows the side
    // it took (data-side) so a flipped menu grows from its trigger.
    $placements = [
        'bottom-start' => 'top-full left-0 mt-2 origin-top-left data-[side=top]:origin-bottom-left',
        'bottom-end' => 'top-full right-0 mt-2 origin-top-right data-[side=top]:origin-bottom-right',
        'top-start' => 'bottom-full left-0 mb-2 origin-bottom-left data-[side=bottom]:origin-top-left',
        'top-end' => 'bottom-full right-0 mb-2 origin-bottom-right data-[side=bottom]:origin-top-right',
    ];

    $placement = array_key_exists($placement, $placements) ? $placement : 'bottom-end';
    $position = $placements[$placement];

    // The trigger wrapper is the menu's previous sibling; the anchor measures a
    // `contents` wrapper by the trigger inside it.
    $anchor = "{ open: {$state}, reference: \$el.previousElementSibling, placement: '{$placement}' }";

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

    // The panel, its transition and the arrow keys are shared with
    // ds::context-menu, from partials/menu.php.
    $menu = \Shipbytes\BladeUi\Support\Menu::recipe();
    $moveFocus = $menu['move'];

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
        {{-- An enhancement: skipped where the script is missing, and the
             absolute placement below is then where the menu opens. --}}
        x-ds-anchor="{{ $anchor }}"
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
        x-transition:enter="{{ $menu['transition']['x-transition:enter'] }}"
        x-transition:enter-start="{{ $menu['transition']['x-transition:enter-start'] }}"
        x-transition:enter-end="{{ $menu['transition']['x-transition:enter-end'] }}"
        x-transition:leave="{{ $menu['transition']['x-transition:leave'] }}"
        x-transition:leave-start="{{ $menu['transition']['x-transition:leave-start'] }}"
        x-transition:leave-end="{{ $menu['transition']['x-transition:leave-end'] }}"
        class="absolute {{ $position }} {{ $menu['panel'] }}"
        role="menu"
        aria-orientation="vertical"
    >
        {{ $slot }}
    </div>
</div>
