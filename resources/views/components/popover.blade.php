@props([
    /**
     * top | bottom | left | right, then -start | -end, or nothing for centred.
     * The PREFERRED side: the panel flips to the opposite one when this side
     * lacks room, and shifts along the edge to stay on screen.
     */
    'placement' => 'bottom-start',
    /**
     * Alpine REFERENCE holding the open state, e.g. `filtersOpen`. Optional,
     * like ds::dropdown's: left out, the component scopes its own, which is
     * what a popover per table row wants. Passed in, it must be assignable,
     * as on ds::modal, because the component sets it back to false.
     */
    'open' => null,
    /** The dialog's name, when nothing visible in it says what it is. */
    'label' => null,
    /** The id of a heading inside the panel that names it. Preferred over `label`. */
    'labelledby' => null,
    /** sm | md | lg — 14, 18 or 24rem, and never wider than the window. */
    'width' => 'md',
    /**
     * Escape, a press outside and focus leaving all close it. Turn off for a
     * panel the host closes itself, from a button inside it.
     */
    'dismissible' => true,
])

@php
    \Shipbytes\BladeUi\Support\OpenState::assertAssignable($open, 'ds::popover');

    $recipe = \Shipbytes\BladeUi\Support\Popover::recipe();
    $placement = \Shipbytes\BladeUi\Support\Popover::placement($placement);
    $width = \Shipbytes\BladeUi\Support\Popover::width($width);

    // Hashed from what the panel holds rather than random: Livewire keys a
    // morph by id, and a fresh id on every response replaces the panel
    // instead of patching it. An `id` passed in names the panel, since that
    // is what the trigger's aria-controls points at.
    $id = $attributes->get('id') ?? 'ds-popover-'.substr(md5($label.$labelledby.$placement.$slot), 0, 8);

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsPopover', ['dismissible' => (bool) $dismissible]);

    // The trigger wrapper is the panel's previous sibling; the anchor
    // measures a `contents` wrapper by the trigger inside it.
    $anchor = '{ open: dsOpen, reference: $el.previousElementSibling, placement: '.\Illuminate\Support\Js::from($placement).' }';

    // Escaped here: an attribute bag built in PHP prints its values as they
    // are, so a quote in a label would end the attribute.
    $panel = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'id' => e($id),
        'role' => 'dialog',
        'aria-labelledby' => $labelledby ? e($labelledby) : null,
        'aria-label' => $labelledby || ! $label ? null : e($label),
        // So the panel can hold focus when it holds nothing focusable.
        'tabindex' => '-1',
        'data-ds-popover-panel' => true,
        'data-side' => \Shipbytes\BladeUi\Support\Popover::side($placement),
        'x-show' => 'dsOpen',
        'x-cloak' => true,
        'x-ds-anchor' => $anchor,
        ...$recipe['transition'],
        'class' => implode(' ', [$recipe['placements'][$placement], $recipe['surface'], $recipe['widths'][$width]]),
    ], fn ($value) => $value !== null));
@endphp

<div
    x-data="{{ $state }}"
    @if ($open) x-modelable="dsOpen" x-model="{{ $open }}" @endif
    data-ds-popover
    {{-- `h-fit` for the reason ds::dropdown gives: in a flex row a stretched
         root would put the fallback position at the floor of the row. --}}
    {{ $attributes->except('id')->merge(['class' => 'relative inline-block h-fit']) }}
>
    <div class="contents" data-ds-popover-trigger>{{ $trigger ?? '' }}</div>
    <div {{ $panel }}>{{ $slot }}</div>
</div>
