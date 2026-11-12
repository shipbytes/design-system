@props([
    /** The text to copy. Give this or `target`, never both. */
    'value' => null,
    /**
     * An element id, or a CSS selector, whose text is copied: a field's value,
     * any other element's text content, trimmed. Read at the moment of the
     * press, so an edited field copies what it holds then.
     */
    'target' => null,
    /** The button's words, and its accessible name when `iconOnly`. */
    'label' => 'Copy',
    /** Shown in the label's place, and announced, for a moment after a copy. */
    'copiedLabel' => 'Copied',
    /** Square, the clipboard icon alone. `label` stays its accessible name. */
    'iconOnly' => false,
    /** primary | secondary | ghost | danger, as ds::button. */
    'variant' => 'secondary',
    /** sm | md | lg, as ds::button. */
    'size' => 'md',
])

@php
    if (($value === null) === ($target === null)) {
        throw new \InvalidArgumentException(
            'ds::copy-button needs exactly one of `value` (the text to copy) or `target` (the id or selector of an element to copy from).'
        );
    }

    $state = \Shipbytes\BladeUi\Support\Behaviour::data(
        'dsClipboard',
        $target !== null ? ['target' => (string) $target] : ['text' => (string) $value],
    );

    /*
     * A refused copy says nothing on screen, so the host is told instead: a
     * `copy-failed` event from the button, which bubbles, for a toast. It is
     * dispatched from the button rather than from the behaviour, so a listener
     * written on the component tag, which lands on the button, hears it.
     */
    $button = $attributes->merge(array_filter([
        'x-on:click' => "copy().then((ok) => ok || \$dispatch('copy-failed'))",
        'aria-label' => $iconOnly ? $label : null,
    ], fn ($v) => $v !== null));

    // The two faces of each part share one grid cell, so the button is as wide
    // as the wider of them from the first paint and does not jump on a copy.
    // The hidden face is `invisible`, which also takes it out of the button's
    // accessible name.
    $face = 'col-start-1 row-start-1';
@endphp

{{-- `contents`: the wrapper only holds the scope, so the button lays out in
     the host's row as a ds::button would. --}}
<span x-data="{{ $state }}" class="contents">
    <x-ds::button :variant="$variant" :size="$size" :iconOnly="$iconOnly" :attributes="$button">
        <span class="grid shrink-0 place-items-center" aria-hidden="true">
            <span class="{{ $face }} flex" x-bind:class="{ 'invisible': copied }"><x-ds::icon name="clipboard-document" size="4" /></span>
            <span class="{{ $face }} flex invisible" x-bind:class="{ 'invisible': ! copied }"><x-ds::icon name="check" size="4" /></span>
        </span>
        @if (! $iconOnly)
            {{-- text-start: a button centres its text, which would centre the
                 shorter face in the cell and open a gap after the icon. --}}
            <span class="grid text-start">
                <span class="{{ $face }}" x-bind:class="{ 'invisible': copied }">{{ $label }}</span>
                <span class="{{ $face }} invisible" x-bind:class="{ 'invisible': ! copied }">{{ $copiedLabel }}</span>
            </span>
        @endif
    </x-ds::button>

    {{-- Outside the button: a change to the focused button's own name is not
         reliably announced, and text inside it would become part of the name. --}}
    <span class="sr-only" aria-live="polite" x-text="copied ? {{ \Illuminate\Support\Js::from($copiedLabel) }} : ''"></span>
</span>
