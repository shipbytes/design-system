@props([
    /** What the setting is. Always required. */
    'label',
    /** Guidance under the label. */
    'help' => null,
    /** Submitted field name. */
    'name' => null,
    'checked' => false,
    'disabled' => false,
    /**
     * Emit a hidden input so the field posts "0" when the switch is off.
     *
     * An unchecked checkbox sends NOTHING, so without this a setting can be
     * turned on and never turned off again through a plain form — the request
     * simply has no key for it and the controller sees "unchanged". Turn it off
     * only when something else owns the value, e.g. wire:model.
     */
    'submitUnchecked' => true,
])

@php
    $id = $attributes->get('id') ?: 'ds-'.substr(md5($name.$label.uniqid()), 0, 8);
    $describedBy = $help ? "{$id}-help" : null;

    /*
     * The real input IS the track — appearance-none, not a hidden input beside a
     * decorated span. Same construction and the same reasons as the checkbox:
     * the substitution loses the browser's focus behaviour and forced-colors
     * support, and both failures are invisible in a normal browser.
     */
    $track = implode(' ', [
        'peer relative h-5 w-9 shrink-0 appearance-none rounded-full transition-colors',
        'bg-fg/20 checked:bg-accent',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        $disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
        'forced-colors:appearance-auto',
    ]);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'flex items-start justify-between gap-4']) }}>
    <div class="min-w-0">
        {{-- The label sits FIRST and the control last, which is the opposite of
             the checkbox. A switch is a setting in a list of settings, and the
             reader scans the names down the left; a checkbox is a choice
             attached to its own sentence. --}}
        <label
            for="{{ $id }}"
            @class([
                'block text-body font-medium text-fg',
                'cursor-pointer' => ! $disabled,
                'cursor-not-allowed text-fg-muted' => $disabled,
            ])
        >{{ $label }}</label>

        @if ($help)
            <p id="{{ $id }}-help" class="mt-0.5 text-meta text-fg-muted">{{ $help }}</p>
        @endif
    </div>

    <span class="relative inline-flex shrink-0 pt-0.5">
        @if ($name && $submitUnchecked && ! $disabled)
            <input type="hidden" name="{{ $name }}" value="0" />
        @endif

        <input
            type="checkbox"
            id="{{ $id }}"
            {{-- role="switch" is what makes a screen reader say "on"/"off"
                 rather than "checked"/"unchecked". The states are the same; the
                 words are not, and "unchecked" for a setting that is simply off
                 reads as a form the reader failed to fill in. --}}
            role="switch"
            value="1"
            {{ $attributes->except(['class', 'id'])->merge(['class' => $track, 'name' => $name]) }}
            @checked($checked)
            @disabled($disabled)
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        />

        {{-- The knob is a sibling rather than a pseudo-element: ::before does not
             render on an <input> in every browser, and finding that out means
             finding it in the one browser that does not. --}}
        <span
            class="pointer-events-none absolute top-1 left-0.5 size-4 rounded-full bg-white shadow-raised transition-transform peer-checked:translate-x-4"
            aria-hidden="true"
        ></span>
    </span>
</div>
