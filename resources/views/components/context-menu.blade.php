@props([
    /** The menu's name for a screen reader, e.g. "Report actions". */
    'label' => null,
    /**
     * Leave the browser's own menu over a field, an editable area or a link
     * inside the region, where copy, paste, spelling and "open in a new tab"
     * live. False opens this menu there too; only for a region whose links and
     * fields have nothing the browser's menu would offer.
     */
    'native' => true,
])

@php
    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsContextMenu', ['native' => (bool) $native]);

    // `$recipe`, not `$menu`: the items arrive in the `menu` slot, as $menu.
    $recipe = \Shipbytes\BladeUi\Support\Menu::recipe();

    // Hung off a box the script measures from the region's corner: the
    // pointer, or the focused element when the keyboard opened it.
    $anchor = '{ open: dsOpen, reference: $el.parentElement, at: dsAt, placement: \'bottom-start\', offset: dsAt.offset }';

    // Fixed from the start, so the panel never takes up room in the region
    // before the anchor has placed it. The origin is the corner at the
    // pointer, whichever way the menu opened from it.
    $classes = 'fixed left-0 top-0 '.$recipe['panel'].' outline-hidden '
        .'origin-top-left data-[align=end]:origin-top-right '
        .'data-[side=top]:origin-bottom-left data-[side=top]:data-[align=end]:origin-bottom-right';
@endphp

<div
    x-data="{{ $state }}"
    data-ds-context-menu
    {{ $attributes }}
>
    {{ $slot }}
    <div
        role="menu"
        aria-orientation="vertical"
        @if ($label) aria-label="{{ $label }}" @endif
        {{-- So the panel can hold focus when every item is disabled. --}}
        tabindex="-1"
        data-ds-context-menu-panel
        data-side="bottom"
        data-align="start"
        x-show="dsOpen"
        x-cloak
        x-ds-anchor="{{ $anchor }}"
        @keydown.arrow-down.prevent="{{ $recipe['move'] }}"
        @keydown.arrow-up.prevent="{{ $recipe['move'] }}"
        @keydown.home.prevent="{{ $recipe['move'] }}"
        @keydown.end.prevent="{{ $recipe['move'] }}"
        x-transition:enter="{{ $recipe['transition']['x-transition:enter'] }}"
        x-transition:enter-start="{{ $recipe['transition']['x-transition:enter-start'] }}"
        x-transition:enter-end="{{ $recipe['transition']['x-transition:enter-end'] }}"
        x-transition:leave="{{ $recipe['transition']['x-transition:leave'] }}"
        x-transition:leave-start="{{ $recipe['transition']['x-transition:leave-start'] }}"
        x-transition:leave-end="{{ $recipe['transition']['x-transition:leave-end'] }}"
        class="{{ $classes }}"
    >{{ $menu ?? '' }}</div>
</div>
