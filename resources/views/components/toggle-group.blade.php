@props([
    /** What the toggles set ("Alignment", "Text style"). The group's accessible name. Required. */
    'label',
    /** single | multiple — one of (radio semantics) or any of (independent pressed buttons). */
    'type' => 'single',
    /** Posts the pressed values through hidden inputs; `[]` is added for multiple. Leave it out to post nothing. */
    'name' => null,
    /** sm | md, for every toggle: 32px or 36px. Inside a toolbar, the toolbar's when not set here. */
    'size' => null,
])

@php
    if (! in_array($type, ['single', 'multiple'], true)) {
        throw new \InvalidArgumentException("ds::toggle-group \"{$label}\": `type` is single or multiple, not \"{$type}\".");
    }

    $single = $type === 'single';
    $tray = \Shipbytes\BladeUi\Support\Tray::recipe();
    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsToggleGroup', ['type' => $type]);

    // wire:model and x-model go on the root beside x-modelable, which ties
    // them to dsValue both ways, as on otp-input. The hidden inputs only post.
    $isModel = fn ($value, $key) => str_starts_with($key, 'wire:model') || str_starts_with($key, 'x-model');
    $model = $attributes->filter($isModel);
@endphp

{{-- Single is a radiogroup of role=radio buttons; multiple, a group of
     aria-pressed buttons. The keyboard is dsToggleGroup's on its own, and the
     toolbar's inside one. The highlight is the toolbar's tray highlight; in a
     toolbar this one stands down and the toolbar's covers these toggles. --}}
<div
    role="{{ $single ? 'radiogroup' : 'group' }}"
    aria-label="{{ $label }}"
    data-ds-toggle-group
    data-ds-glide
    x-data="{{ $state }}"
    x-modelable="dsValue"
    {{ $model }}
    x-ds-glide.hover
    {{ $attributes->filter(fn ($value, $key) => ! $isModel($value, $key))->merge(['class' => $tray['group']]) }}
>
    <span data-ds-glide-highlight hidden aria-hidden="true" class="{{ $tray['highlight'] }}"></span>
    {{ $slot }}
</div>
