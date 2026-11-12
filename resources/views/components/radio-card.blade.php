@aware([
    'name' => null,
])

@props([
    /** What this card posts when chosen. */
    'value',
    /** The option's name: "Pro", "Express". */
    'title',
    /** One line under the title. */
    'description' => null,
    'checked' => false,
    'disabled' => false,
])

@php
    if ($name === null || $name === '') {
        throw new \InvalidArgumentException("ds::radio-card \"{$title}\" needs a `name`, normally from its ds::radio-cards.");
    }

    /*
     * Named from its parts, so the ids are the same on every render: Livewire
     * morphs by id, and a card re-rendered with a new one would be replaced,
     * losing focus mid-choice. A name and value are unique in a form already;
     * pass `id` for the same choice twice on one page.
     */
    $id = (string) ($attributes->get('id') ?: 'ds-card-'.substr(md5($name.'|'.$value), 0, 8));

    $hasMeta = isset($meta) && trim((string) $meta) !== '';
    $hasIcon = isset($icon) && trim((string) $icon) !== '';
    $hasBody = trim((string) $slot) !== '';

    // The name is the title, then the meta, so "Pro, $12 a month" is heard as
    // one; the description and body are read after it, as its description.
    $labelledBy = trim("{$id}-title".($hasMeta ? " {$id}-meta" : ''));
    $describedBy = trim(($description ? "{$id}-description " : '').($hasBody ? "{$id}-body" : ''));

    $card = implode(' ', [
        'relative flex items-start gap-3 rounded-control border p-4',
        'motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
        'has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus-ring',
        $disabled
            ? 'cursor-not-allowed border-border bg-surface-subtle has-checked:border-border-strong'
            : 'cursor-pointer border-border bg-surface hover:border-fg/20 has-checked:border-accent has-checked:ring-1 has-checked:ring-accent has-checked:bg-accent-wash',
    ]);

    // ds::radio's control, kept: a real radio, styled, so forced colours and
    // the browser's own state survive. Its focus is drawn on the card.
    $control = implode(' ', [
        'peer col-start-1 row-start-1 size-4.5 shrink-0 appearance-none rounded-full',
        'border border-border-strong bg-surface transition-colors',
        'checked:border-accent checked:bg-accent',
        'focus-visible:outline-none',
        $disabled
            ? 'cursor-not-allowed bg-surface-subtle checked:border-fg-subtle checked:bg-fg-subtle'
            : 'cursor-pointer',
        'forced-colors:appearance-auto',
    ]);
@endphp

<label data-ds-radio-card for="{{ $id }}" {{ $attributes->only('class')->merge(['class' => $card]) }}>
    <span class="grid shrink-0 grid-cols-1 grid-rows-1 place-items-center pt-0.5">
        {{-- The hook is swapped by the group for the host's binding and
             `required`. --}}
        <input
            data-ds-radio-card-input
            type="radio"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $value }}"
            aria-labelledby="{{ $labelledBy }}"
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->except(['class', 'id'])->merge(['class' => $control]) }}
            @checked($checked)
            @disabled($disabled)
        />
        <span class="pointer-events-none col-start-1 row-start-1 size-1.5 rounded-full bg-on-accent opacity-0 peer-checked:opacity-100" aria-hidden="true"></span>
    </span>

    @if ($hasIcon)
        <span @class(['flex shrink-0 pt-0.5', 'text-fg-muted' => $disabled, 'text-fg-body' => ! $disabled]) aria-hidden="true">{{ $icon }}</span>
    @endif

    <span class="min-w-0 flex-1">
        <span class="flex items-start justify-between gap-3">
            <span id="{{ $id }}-title" @class(['text-body font-medium', 'text-fg' => ! $disabled, 'text-fg-muted' => $disabled])>{{ $title }}</span>
            @if ($hasMeta)
                <span id="{{ $id }}-meta" @class(['shrink-0 text-end text-body font-medium tabular-nums', 'text-fg' => ! $disabled, 'text-fg-muted' => $disabled])>{{ $meta }}</span>
            @endif
        </span>
        @if ($description)
            <span id="{{ $id }}-description" class="mt-0.5 block text-meta text-fg-muted">{{ $description }}</span>
        @endif
        @if ($hasBody)
            <span id="{{ $id }}-body" @class(['mt-2 block text-meta', 'text-fg-body' => ! $disabled, 'text-fg-muted' => $disabled])>{{ $slot }}</span>
        @endif
    </span>
</label>
