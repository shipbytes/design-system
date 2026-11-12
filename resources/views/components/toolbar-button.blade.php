@aware([
    'size' => 'md',
    'orientation' => 'horizontal',
])

@props([
    /** The words, and the accessible name. With `iconOnly`, the name only. */
    'label',
    /** A Heroicons name, drawn before the words. */
    'icon' => null,
    /** Square, the icon alone; `label` stays the accessible name. Needs an `icon`. */
    'iconOnly' => false,
    /**
     * A toggle's state: true or false makes it a toggle button (aria-pressed),
     * null a plain one. Rendered, not managed: the host flips it (a Livewire
     * action, or `:aria-pressed` bound in Alpine). For toggles that hold their
     * own state, and post it, use ds::toggle-group.
     */
    'pressed' => null,
    'disabled' => false,
    /** A link instead of a button. A link cannot be pressed: not with `pressed`. */
    'href' => null,
    /** A tip on hover and focus: a string, or true for the label. Mostly for `iconOnly`. */
    'tooltip' => null,
])

@php
    if ($href !== null && $pressed !== null) {
        throw new \InvalidArgumentException("ds::toolbar-button \"{$label}\": a link has no pressed state. Drop `href` for a toggle, or `pressed` for a link.");
    }
    if ($iconOnly && ! $icon) {
        throw new \InvalidArgumentException("ds::toolbar-button \"{$label}\": `iconOnly` needs an `icon` to show.");
    }

    $size = in_array($size, ['sm', 'md'], true) ? $size : 'md';
    $link = $href !== null;
    $tray = \Shipbytes\BladeUi\Support\Tray::recipe();

    $item = $attributes->except(['size', 'orientation'])->merge(['class' => $tray['item']($size, (bool) $iconOnly)])
        ->merge(array_filter([
            // The hooks the toolbar's keyboard and highlight find their items by.
            'data-ds-toolbar-item' => '',
            'data-ds-glide-item' => '',
            'type' => $link ? null : 'button',
            'href' => $link && ! $disabled ? $href : null,
            'aria-pressed' => $pressed === null ? null : ($pressed ? 'true' : 'false'),
            'disabled' => ! $link && $disabled ? true : null,
            'aria-disabled' => $link && $disabled ? 'true' : null,
        ], fn ($v) => $v !== null));
@endphp

@include('ds::partials.tray-item', [
    'tag' => $link ? 'a' : 'button',
    'item' => $item,
    'label' => $label,
    'icon' => $icon,
    'iconOnly' => (bool) $iconOnly,
    'tooltip' => $tooltip,
    'placement' => $orientation === 'vertical' ? 'right' : 'top',
    'inside' => null,
])
