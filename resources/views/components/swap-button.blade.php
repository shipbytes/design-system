{{--
    ds::swap-button. `swapped` is read from the attribute bag BEFORE @props,
    on purpose: the server state and the second face share the name
    `swapped`, and Blade hands a named slot to the view under the variable a
    prop of that name would take, so a declared prop would be overwritten by
    the slot and the state lost. The bag still holds the state; the variable
    is the slot.
--}}
@php
    $swappedState = filter_var($attributes->get('swapped', false), FILTER_VALIDATE_BOOLEAN);
    $attributes = $attributes->except('swapped');
@endphp

@props([
    /**
     * Milliseconds the swapped face shows before the button swaps back by
     * itself. 0 keeps it swapped: with `swapOnClick` that makes a toggle,
     * "Follow" and "Following", with aria-pressed.
     */
    'revert' => 2000,
    /**
     * An event name: the button swaps when it reaches the window. A Livewire
     * `$this->dispatch('saved')` after the save, or anything on the page
     * that bubbles.
     */
    'event' => null,
    /** A press swaps it. On by default, unless `event` is set: then the event says when. */
    'swapOnClick' => null,
    /** primary | secondary | ghost | danger, as ds::button. */
    'variant' => 'secondary',
    /** sm | md | lg, as ds::button. */
    'size' => 'md',
])

@php
    if (! isset($swapped) || ! $swapped instanceof \Illuminate\View\ComponentSlot || $swapped->isEmpty()) {
        throw new \InvalidArgumentException(
            'ds::swap-button needs a `swapped` slot: the face it swaps to, "Saved" or "Following". Without one there is nothing to swap.'
        );
    }

    $revert = max(0, (int) $revert);
    $swapOnClick = $swapOnClick === null ? $event === null : (bool) $swapOnClick;
    // A toggle is a button that stays swapped until pressed again, and its
    // name must stay put while its state changes (the APG button pattern):
    // the face at rest names it, aria-pressed carries the state.
    $toggle = $revert === 0 && $swapOnClick;

    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsSwap', array_filter([
        'swapped' => $swappedState,
        'revert' => $revert,
        'click' => $swapOnClick,
        'event' => $event !== null ? (string) $event : null,
    ], fn ($v) => $v !== null));

    $swap = \Shipbytes\BladeUi\Support\Swap::recipe();

    // Hashed rather than random, for a Livewire morph: the toggle's name
    // points at the face at rest by this id.
    $id = 'ds-swap-'.substr(md5($slot.'|'.$swapped), 0, 8);

    $button = $attributes->merge(array_filter([
        'data-ds-swap-button' => true,
        'data-ds-swapped' => $swappedState ? '' : null,
        'x-bind:data-ds-swapped' => "dsSwapped ? '' : null",
        'aria-pressed' => $toggle ? ($swappedState ? 'true' : 'false') : null,
        'x-bind:aria-pressed' => $toggle ? "dsSwapped ? 'true' : 'false'" : null,
        'aria-labelledby' => $toggle ? "{$id}-rest" : null,
    ], fn ($v) => $v !== null))->class('group/swap');
@endphp

{{-- `contents`: the wrapper only holds the scope, so the button lays out in
     the host's row as a ds::button would. --}}
<span x-data="{{ $state }}" class="contents" data-ds-swap>
    <x-ds::button :variant="$variant" :size="$size" :attributes="$button">
        {{-- justify-items-center: a face narrower than the other sits in the
             middle of the width the wider one reserves. --}}
        <span class="{{ $swap['stack'] }} justify-items-center">
            <span
                id="{{ $id }}-rest"
                class="{{ $swap['rest'] }} inline-flex items-center gap-2"
                data-ds-swap-face
                @if ($swappedState) aria-hidden="true" inert @endif
                x-bind:aria-hidden="dsSwapped ? 'true' : null"
                x-bind:inert="dsSwapped"
            >{{ $slot }}</span>
            <span
                class="{{ $swap['swapped'] }} inline-flex items-center gap-2"
                data-ds-swap-face
                @unless ($swappedState) aria-hidden="true" inert @endunless
                x-bind:aria-hidden="dsSwapped ? null : 'true'"
                x-bind:inert="! dsSwapped"
            >{{ $swapped }}</span>
        </span>
    </x-ds::button>

    {{-- Outside the button: text inside it would join its name, and a change
         to a focused button's own name is not reliably announced. --}}
    <span class="sr-only" role="status" data-ds-swap-status></span>
</span>
