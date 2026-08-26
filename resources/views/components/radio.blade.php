@props([
    /** Label text. Always required. */
    'label',
    /** Guidance under the label. */
    'help' => null,
    'checked' => false,
    'disabled' => false,
])

@php
    $id = $attributes->get('id') ?: 'ds-'.substr(md5($attributes->get('name', '').$label.uniqid()), 0, 8);
    $describedBy = $help ? "{$id}-help" : null;

    // Same construction as the checkbox — the native input, styled, rather than
    // a hidden one beside a decorated span. See checkbox.blade.php for why.
    $control = implode(' ', [
        'peer col-start-1 row-start-1 size-4.5 shrink-0 appearance-none rounded-full',
        'border bg-surface transition-colors',
        'checked:border-accent checked:bg-accent',
        'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        'border-border-strong',
        $disabled
            ? 'cursor-not-allowed bg-surface-subtle checked:border-fg-subtle checked:bg-fg-subtle'
            : 'cursor-pointer hover:border-fg/30',
        'forced-colors:appearance-auto',
    ]);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'flex gap-2.5']) }}>
    <div class="grid shrink-0 grid-cols-1 grid-rows-1 place-items-center pt-0.5">
        <input
            type="radio"
            id="{{ $id }}"
            {{ $attributes->except(['class', 'id'])->merge(['class' => $control]) }}
            @checked($checked)
            @disabled($disabled)
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        />

        {{-- A dot, not a tick. The shape is the difference between "one of these"
             and "any of these", and it is the only difference a reader gets. --}}
        <span
            class="pointer-events-none col-start-1 row-start-1 size-1.5 rounded-full bg-on-accent opacity-0 peer-checked:opacity-100"
            aria-hidden="true"
        ></span>
    </div>

    <div class="min-w-0">
        <label
            for="{{ $id }}"
            @class([
                'block text-body text-fg',
                'cursor-pointer' => ! $disabled,
                'cursor-not-allowed text-fg-muted' => $disabled,
            ])
        >{{ $label }}</label>

        @if ($help)
            <p id="{{ $id }}-help" class="mt-0.5 text-meta text-fg-muted">{{ $help }}</p>
        @endif
    </div>
</div>
