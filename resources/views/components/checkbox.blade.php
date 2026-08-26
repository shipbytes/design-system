@props([
    /** Label text. Always required — an unlabelled checkbox is unusable and untappable. */
    'label',
    /** Guidance under the label. Replaced by `error`, never stacked with it. */
    'help' => null,
    /** Validation message. Its presence styles the control and sets aria-invalid. */
    'error' => null,
    /** Neither checked nor unchecked — a parent whose children disagree. */
    'indeterminate' => false,
    'checked' => false,
    'disabled' => false,
])

@php
    $id = $attributes->get('id') ?: 'ds-'.substr(md5($attributes->get('name', '').$label.uniqid()), 0, 8);
    $describedBy = $error ? "{$id}-error" : ($help ? "{$id}-help" : null);

    /*
     * The native input, styled — not a hidden input beside a decorated <span>.
     *
     * A visually-hidden checkbox with a fake box next to it loses the Windows
     * high-contrast rendering, the browser's own focus behaviour and forced-colors
     * support, and every one of those failures is invisible in a normal browser.
     * `appearance-none` keeps the element and takes only its paint.
     */
    $control = implode(' ', [
        'peer col-start-1 row-start-1 size-4.5 shrink-0 appearance-none rounded-chip',
        'border bg-surface transition-colors',
        'checked:border-accent checked:bg-accent',
        'indeterminate:border-accent indeterminate:bg-accent',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        $error ? 'border-danger' : 'border-border-strong',
        $disabled
            ? 'cursor-not-allowed bg-surface-subtle checked:bg-fg-subtle checked:border-fg-subtle'
            : 'cursor-pointer hover:border-fg/30',
        // forced-colors: the fill is removed by the OS palette, so the mark has
        // to survive on its own. Without this the box looks empty when checked.
        'forced-colors:appearance-auto',
    ]);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'flex gap-2.5']) }}>
    {{-- The input and its mark are stacked in ONE grid cell rather than the mark
         being absolutely positioned. Absolute positioning against a label that
         wraps to two lines drifts; a grid cell cannot. --}}
    <div class="grid shrink-0 grid-cols-1 grid-rows-1 place-items-center pt-0.5">
        <input
            type="checkbox"
            id="{{ $id }}"
            {{ $attributes->except(['class', 'id'])->merge(['class' => $control]) }}
            @checked($checked)
            @disabled($disabled)
            @if ($error) aria-invalid="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($indeterminate)
                {{-- `indeterminate` is a DOM PROPERTY. There is no attribute that
                     sets it, so this needs a line of script — x-init where the host
                     has Alpine, or one line of their own where it does not.

                     Note what the styling keys off: the `:indeterminate` pseudo-
                     class, i.e. the property itself, NOT this prop. So in a host
                     where nothing sets it, the box renders unchecked — which is
                     exactly what a screen reader announces. The feature is absent
                     rather than lying, and a developer testing it sees that at
                     once. Styling it from the prop instead would draw a mixed mark
                     over a control reporting "unchecked". --}}
                x-init="$el.indeterminate = true"
            @endif
        />

        {{-- pointer-events-none so the mark never swallows the click that belongs
             to the input underneath it. --}}
        <svg
            class="pointer-events-none col-start-1 row-start-1 size-3 text-on-accent opacity-0 peer-checked:opacity-100 peer-indeterminate:opacity-0"
            viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
        >
            <path d="M2.5 6.5 5 9l4.5-5.5" />
        </svg>

        <svg
            class="pointer-events-none col-start-1 row-start-1 size-3 text-on-accent opacity-0 peer-indeterminate:opacity-100"
            viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" aria-hidden="true"
        >
            <path d="M2.5 6h7" />
        </svg>
    </div>

    <div class="min-w-0">
        {{-- The label is a real <label for>, so its whole width is a click target.
             That is most of the touch target on a phone. --}}
        <label
            for="{{ $id }}"
            @class([
                'block text-body text-fg',
                'cursor-pointer' => ! $disabled,
                'cursor-not-allowed text-fg-muted' => $disabled,
            ])
        >{{ $label }}</label>

        @if ($error)
            <p id="{{ $id }}-error" class="mt-0.5 text-meta text-danger">{{ $error }}</p>
        @elseif ($help)
            <p id="{{ $id }}-help" class="mt-0.5 text-meta text-fg-muted">{{ $help }}</p>
        @endif
    </div>
</div>
