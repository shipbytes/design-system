@props([
    /**
     * top | bottom | left | right, then -start | -end, or nothing for centred.
     * Preferred, as on ds::popover: it flips and shifts to stay on screen.
     */
    'placement' => 'bottom-start',
    /** sm | md | lg — 14, 18 or 24rem, and never wider than the window. */
    'width' => 'md',
])

@php
    $recipe = \Shipbytes\BladeUi\Support\Popover::recipe();
    $placement = \Shipbytes\BladeUi\Support\Popover::placement($placement);
    $width = \Shipbytes\BladeUi\Support\Popover::width($width);

    // Hashed, not random, for Livewire's morph; see ds::popover.
    $id = $attributes->get('id') ?? 'ds-hover-card-'.substr(md5($placement.($trigger ?? '').$slot), 0, 8);

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsHoverCard');

    $anchor = '{ open: dsOpen, reference: $el.previousElementSibling, placement: '.\Illuminate\Support\Js::from($placement).' }';

    /*
     * No role. Not `tooltip`, which may not hold anything interactive and
     * which this can (a profile link, a follow button); not `dialog`, since
     * focus never moves into it. It is plain content that follows the
     * trigger in the page, reachable by Tab while it is shown.
     *
     * `block` on a span: the whole component is spans, so it can sit inside a
     * paragraph, where a div would end the paragraph before it.
     */
    $panel = new \Illuminate\View\ComponentAttributeBag([
        'id' => e($id),
        'data-ds-hover-card-panel' => true,
        'data-side' => \Shipbytes\BladeUi\Support\Popover::side($placement),
        'x-show' => 'dsOpen',
        'x-cloak' => true,
        'x-ds-anchor' => $anchor,
        ...$recipe['transition'],
        'class' => implode(' ', ['block', $recipe['placements'][$placement], $recipe['surface'], $recipe['widths'][$width]]),
    ]);
@endphp

<span
    x-data="{{ $state }}"
    data-ds-hover-card
    {{ $attributes->except('id')->merge(['class' => 'relative inline-block h-fit']) }}
>
    <span class="contents" data-ds-hover-card-trigger>{{ $trigger ?? '' }}</span>
    <span {{ $panel }}>{{ $slot }}</span>
</span>
