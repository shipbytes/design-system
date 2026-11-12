@aware([
    'name' => null,
    'multiple' => false,
    'size' => 'md',
])

@props([
    /** What this chip posts when chosen. */
    'value' => null,
    'checked' => false,
    /** A Heroicons name, drawn before the words. */
    'icon' => null,
    /** A number after the words: how many results the filter leaves. */
    'count' => null,
    'disabled' => false,
    /** A link to a filtered view instead of a value to post. Every chip in a group is one or the other. */
    'href' => null,
    /** The link chip for the view on screen: drawn chosen, marked aria-current. */
    'active' => false,
])

@php
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $link = $href !== null;
    $on = $link ? (bool) $active : null;

    if (! $link) {
        if ($name === null || $name === '') {
            throw new \InvalidArgumentException('ds::chip needs a `name`, normally from its ds::chip-group, unless it is a link chip with `href`.');
        }
        if ($value === null) {
            throw new \InvalidArgumentException("ds::chip in \"{$name}\" needs a `value` to post.");
        }
    }

    $shape = [
        'sm' => 'h-7 px-2.5 text-meta',
        'md' => 'h-8 px-3 text-meta',
        'lg' => 'h-10 px-4 text-body',
    ][$size];
    $glyph = $size === 'sm' ? '3.5' : '4';

    /*
     * Chosen is drawn by `has-checked:` on the label for an input chip, and by
     * the same classes written out for an active link, so the two look alike.
     * The check is the cue that is not colour.
     */
    $chosen = 'border-accent bg-accent-tint text-on-accent-tint';
    $resting = $disabled
        ? 'border-border bg-surface-subtle text-fg-muted'
        : 'border-border bg-surface text-fg-body hover:border-fg/20 hover:bg-surface-subtle';

    $chip = implode(' ', array_filter([
        'relative inline-flex shrink-0 items-center rounded-full border font-medium whitespace-nowrap select-none',
        'motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
        $shape,
        $link ? ($on ? $chosen : $resting) : $resting,
        // Chosen and disabled stays visibly chosen, the check open, but grey.
        match (true) {
            $link => null,
            $disabled => 'has-checked:border-border-strong',
            default => 'has-checked:border-accent has-checked:bg-accent-tint has-checked:text-on-accent-tint',
        },
        $disabled ? 'cursor-not-allowed' : 'cursor-pointer',
        $link
            ? 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring'
            : 'has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-focus-ring',
    ]));

    /*
     * The check opens from no width as the chip is chosen, so the words slide
     * over by its width rather than jumping. Without motion it is instant. Its
     * cell is the icon plus the gap after it, so nothing else needs a margin
     * that would be there while it is closed.
     */
    $open = $size === 'sm'
        ? ['open' => 'w-4.5', 'closed' => 'w-0', 'input' => 'w-0 peer-checked:w-4.5']
        : ['open' => 'w-5', 'closed' => 'w-0', 'input' => 'w-0 peer-checked:w-5'];
    $check = implode(' ', [
        'flex shrink-0 overflow-hidden',
        'motion-safe:transition-[width] motion-safe:duration-popover-enter motion-safe:ease-enter',
        $link ? $open[$on ? 'open' : 'closed'] : $open['input'],
    ]);
@endphp

@if ($link)
    <a
        data-ds-chip="link"
        @unless ($disabled) href="{{ $href }}" @endunless
        @if ($on) aria-current="true" @endif
        @if ($disabled) aria-disabled="true" @endif
        {{ $attributes->merge(['class' => $chip]) }}
    >
        <span class="{{ $check }}" aria-hidden="true"><x-ds::icon name="check" variant="micro" :size="$glyph" /></span>
        @if ($icon)
            <x-ds::icon :name="$icon" variant="micro" :size="$glyph" class="me-1.5" />
        @endif
        <span>{{ $slot }}</span>
        @if ($count !== null)
            <span class="ms-1.5 tabular-nums opacity-80">{{ $count }}</span>
        @endif
    </a>
@else
    <label data-ds-chip="input" {{ $attributes->only('class')->merge(['class' => $chip]) }}>
        {{-- sr-only, not hidden: it keeps focus, the arrow keys and form
             validation, and the label around it carries the look. The hook is
             swapped by the group for the host's wire:model or x-model. --}}
        <input
            data-ds-chip-input
            type="{{ $multiple ? 'checkbox' : 'radio' }}"
            name="{{ $multiple ? $name.'[]' : $name }}"
            value="{{ $value }}"
            {{ $attributes->except('class')->merge(['class' => 'peer sr-only']) }}
            @checked($checked)
            @disabled($disabled)
        />
        <span class="{{ $check }}" aria-hidden="true"><x-ds::icon name="check" variant="micro" :size="$glyph" /></span>
        @if ($icon)
            <x-ds::icon :name="$icon" variant="micro" :size="$glyph" class="me-1.5" />
        @endif
        <span>{{ $slot }}</span>
        @if ($count !== null)
            <span class="ms-1.5 tabular-nums opacity-80">{{ $count }}</span>
        @endif
    </label>
@endif
