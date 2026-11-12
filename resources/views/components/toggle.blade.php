@aware([
    'type' => 'single',
    'name' => null,
    'size' => null,
])

@props([
    /** What this toggle stands for, and posts while pressed. */
    'value',
    /** The words, and the accessible name. With `iconOnly`, the name only. */
    'label',
    /** A Heroicons name, drawn before the words. */
    'icon' => null,
    /** Square, the icon alone; `label` stays the accessible name. Needs an `icon`. */
    'iconOnly' => false,
    /** Pressed on the first paint: checked, in a single group. */
    'pressed' => false,
    'disabled' => false,
    /** A tip on hover and focus: a string, or true for the label. Mostly for `iconOnly`. */
    'tooltip' => null,
])

@php
    if ($iconOnly && ! $icon) {
        throw new \InvalidArgumentException("ds::toggle \"{$label}\": `iconOnly` needs an `icon` to show.");
    }

    $single = $type !== 'multiple';
    // The group's size, else a surrounding toolbar's (both reach here through
    // @aware), else md.
    $size = in_array($size, ['sm', 'md'], true) ? $size : 'md';
    $on = (bool) $pressed;
    $tray = \Shipbytes\BladeUi\Support\Tray::recipe();

    $item = $attributes->except(['type', 'name', 'size'])->merge(['class' => $tray['item']($size, (bool) $iconOnly)])
        ->merge(array_filter([
            'type' => 'button',
            'data-ds-toggle' => '',
            'data-ds-value' => (string) $value,
            // A toggle in a toolbar is one of the toolbar's items: its arrow
            // keys and its highlight reach it by these.
            'data-ds-toolbar-item' => '',
            'data-ds-glide-item' => '',
            'role' => $single ? 'radio' : null,
            'aria-checked' => $single ? ($on ? 'true' : 'false') : null,
            'aria-pressed' => $single ? null : ($on ? 'true' : 'false'),
            'disabled' => $disabled ? true : null,
        ], fn ($v) => $v !== null));

    // Inside the button, which a hidden input may be: it is not interactive
    // content. Disabled while the toggle is off, so only pressed values post,
    // and disabled for a disabled toggle, as a disabled checkbox posts nothing.
    $inside = $name === null || $name === '' ? null : new \Illuminate\Support\HtmlString(
        '<input type="hidden" data-ds-toggle-input name="'.e($single ? $name : $name.'[]').'" value="'.e((string) $value).'"'
        .(! $on || $disabled ? ' disabled' : '').' />'
    );
@endphp

@include('ds::partials.tray-item', [
    'tag' => 'button',
    'item' => $item,
    'label' => $label,
    'icon' => $icon,
    'iconOnly' => (bool) $iconOnly,
    'tooltip' => $tooltip,
    'placement' => 'top',
    'inside' => $inside,
])
