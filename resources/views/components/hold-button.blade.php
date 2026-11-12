@props([
    /**
     * The instruction, which is also the button's words and name: "Hold to
     * delete". The component writes it so no host can leave it out.
     */
    'label' => 'Hold to confirm',
    /** Shown in its place, and announced, after a first press that did not hold. */
    'armedLabel' => 'Press again to confirm',
    /** Milliseconds of holding that confirm. */
    'duration' => 1500,
    /** danger | neutral: the outline at rest and the colour it fills with. */
    'tone' => 'danger',
    /** sm | md | lg, as ds::button. */
    'size' => 'md',
    /**
     * button | submit. submit sends the form around it once confirmed, with
     * this button as the submitter. It is rendered type=button either way, so
     * no single press can submit anything, script or no script.
     */
    'type' => 'button',
])

@php
    $tone = $tone === 'neutral' ? 'neutral' : 'danger';
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsHold', [
        'duration' => max(1, (int) $duration),
        'submit' => $type === 'submit',
    ]);

    $fill = [
        'danger' => 'bg-danger text-on-danger',
        'neutral' => 'bg-surface-inverse text-on-inverse',
    ][$tone];

    /*
     * The host's attributes go on the button: an id, a name and value to
     * post, an @confirm. overflow-hidden clips the fill to the button's
     * corners. A finger held on it must not scroll the page, select its words
     * or open the browser's long-press menu, any of which ends the hold.
     */
    $button = $attributes->merge([
        'type' => 'button',
        'data-ds-hold' => true,
        'class' => 'overflow-hidden select-none touch-none [-webkit-touch-callout:none]',
    ]);

    $face = 'col-start-1 row-start-1';
    $armed = \Illuminate\Support\Js::from($armedLabel);
@endphp

{{-- `contents`: the wrapper only holds the scope, so the button lays out in
     the host's row as a ds::button would. --}}
<span x-data="{{ $state }}" class="contents">
    <x-ds::button variant="secondary" :tone="$tone" :size="$size" :attributes="$button">
        {{-- The two instructions share one cell, so the button is as wide as
             the longer from the first paint and never moves when it arms;
             the shorter sits in the middle of it. --}}
        <span class="grid text-center">
            <span class="{{ $face }}" x-bind:class="{ 'invisible': $data.dsArmed === true }">{{ $label }}</span>
            <span class="{{ $face }} invisible" x-bind:class="{ 'invisible': $data.dsArmed !== true }">{{ $armedLabel }}</span>
        </span>

        {{--
            The fill: the same words again, in the filled colours, revealed
            from the inline start as --ds-hold-rest falls from 100% to 0. A
            second copy rather than a bar behind the words, so the words stay
            legible on both sides of the edge as it passes them.
        --}}
        <span
            class="pointer-events-none absolute inset-0 flex items-center justify-center {{ $fill }} [clip-path:inset(0_var(--ds-hold-rest,100%)_0_0)] rtl:[clip-path:inset(0_0_0_var(--ds-hold-rest,100%))]"
            aria-hidden="true"
            data-ds-hold-fill
        >
            <span class="grid text-center">
                <span class="{{ $face }}" x-bind:class="{ 'invisible': $data.dsArmed === true }">{{ $label }}</span>
                <span class="{{ $face }} invisible" x-bind:class="{ 'invisible': $data.dsArmed !== true }">{{ $armedLabel }}</span>
            </span>
        </span>
    </x-ds::button>

    {{-- Outside the button: a change to the focused button's own name is not
         reliably announced. --}}
    <span class="sr-only" aria-live="polite" x-text="$data.dsArmed ? {{ $armed }} : ''"></span>
</span>
