@props([
    /** The button's words at rest. */
    'label' => 'Delete',
    /** The confirm control's words. Defaults to `label`. */
    'confirmLabel' => null,
    /** The safe choice's words. */
    'cancelLabel' => 'Cancel',
    /** Asked in the button's place, and announced. */
    'question' => 'Are you sure?',
    /** The button at rest: primary | secondary | ghost | danger. secondary and ghost are drawn in the danger tone. */
    'variant' => 'danger',
    /** sm | md | lg, as ds::button; every part takes it. */
    'size' => 'md',
    /**
     * Seconds the question waits before it puts itself away. The count stops
     * while the pointer is over it or focus is in it, and starts again from
     * the top once both have left. 0 never times out.
     */
    'timeout' => 5,
    /**
     * After the confirm press, show a spinner on it and refuse every later
     * press, until the page goes (a form submitting) or the component does
     * (a row the action removes). For an action that runs for a while.
     */
    'loading' => false,
    /**
     * The confirm control's type. submit, so the whole thing works inside a
     * form; button for one that only dispatches `confirm` or carries a
     * wire:click, inside a form it must not submit.
     */
    'type' => 'submit',
])

@php
    $variant = in_array($variant, ['primary', 'secondary', 'ghost', 'danger'], true) ? $variant : 'danger';
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $type = $type === 'button' ? 'button' : 'submit';
    $confirmLabel ??= $label;

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsConfirm', [
        'timeout' => max(0, (int) round(((float) $timeout) * 1000)),
        'loading' => (bool) $loading,
    ]);

    // The question's type is the button's, so the row keeps one size of text.
    $questionText = [
        'sm' => 'text-meta',
        'md' => 'text-body-touch sm:text-body',
        'lg' => 'text-body-touch',
    ][$size];

    /*
     * Everything the host wrote on the tag but `class` lands on the confirm
     * control: its wire:click, its @confirm, a name and value to post. The
     * first press is the component's alone, so nothing the host hung on the
     * action can run from it. `class` places the whole thing in the host's row.
     */
    $control = $attributes->except('class')->merge([
        'type' => $type,
        'data-ds-confirm-confirm' => true,
        'x-bind:aria-busy' => "\$data.dsBusy ? 'true' : null",
        'x-bind:aria-disabled' => "\$data.dsBusy ? 'true' : null",
        'x-bind:class' => "{ 'cursor-wait': \$data.dsBusy === true }",
    ]);
@endphp

{{--
    One inline row whose width follows the face on show: at rest the button's,
    while asking the question's. It grows toward the inline end, so in a row
    that is end-aligned (a table's action cell, a dialog's footer) it opens
    into the free space and moves nothing after it. Nothing in it wraps, so
    its height never changes.
--}}
<span
    x-data="{{ $state }}"
    data-ds-confirm
    {{ $attributes->only('class')->merge(['class' => 'inline-flex items-center align-middle']) }}
>
    <x-ds::button
        :variant="$variant"
        :tone="in_array($variant, ['secondary', 'ghost'], true) ? 'danger' : 'neutral'"
        :size="$size"
        data-ds-confirm-trigger
        x-show="! $data.dsAsking"
    >{{ $label }}</x-ds::button>

    {{-- Hidden from the server: until the script shows it, the action is
         not on screen, so a page without it fails safe. --}}
    <span
        class="inline-flex items-center gap-2 whitespace-nowrap"
        data-ds-confirm-ask
        x-show="$data.dsAsking === true"
        x-transition:enter="motion-safe:transition-opacity motion-safe:duration-popover-enter motion-safe:ease-enter"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        style="display: none"
    >
        <span class="font-medium text-fg {{ $questionText }}">{{ $question }}</span>
        <x-ds::button variant="secondary" :size="$size" data-ds-confirm-cancel x-bind:disabled="$data.dsBusy === true">{{ $cancelLabel }}</x-ds::button>
        <x-ds::button variant="danger" :size="$size" :attributes="$control">
            <svg class="size-4 shrink-0 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.5" stroke-linecap="round" aria-hidden="true" x-show="$data.dsBusy === true" style="display: none">
                <path d="M12 3a9 9 0 1 0 9 9" />
            </svg>
            {{ $confirmLabel }}
        </x-ds::button>
    </span>

    {{-- Outside the buttons: text inside one would join its name. --}}
    <span class="sr-only" aria-live="polite" x-text="$data.dsAsking ? {{ \Illuminate\Support\Js::from($question) }} : ''"></span>
</span>
