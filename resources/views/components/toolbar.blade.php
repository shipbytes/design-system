@props([
    /** What the toolbar is for ("Text formatting", "3 selected"). Its accessible name. Required. */
    'label',
    /** muted | floating — floating adds a hairline and the overlay shadow, for a tray over content. */
    'variant' => 'muted',
    /** sm | md, for every button in it: 32px or 36px. */
    'size' => 'md',
    /** horizontal | vertical — which arrow keys move, and which way it lays out. */
    'orientation' => 'horizontal',
])

@php
    $vertical = $orientation === 'vertical';
    $tray = \Shipbytes\BladeUi\Support\Tray::recipe();
    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsToolbar');
@endphp

{{-- One tab stop and the arrow keys (dsToolbar), and a highlight that follows
     the pointer and keyboard focus (x-ds-glide.hover). The highlight is the
     first child so every item, positioned after it, paints above it. --}}
<div
    role="toolbar"
    aria-label="{{ $label }}"
    aria-orientation="{{ $vertical ? 'vertical' : 'horizontal' }}"
    data-ds-toolbar
    data-ds-glide
    x-data="{{ $state }}"
    x-ds-glide.hover
    {{ $attributes->merge(['class' => $tray['tray']($variant === 'floating' ? 'floating' : 'muted', $vertical)]) }}
>
    <span data-ds-glide-highlight hidden aria-hidden="true" class="{{ $tray['highlight'] }}"></span>
    {{ $slot }}
</div>
