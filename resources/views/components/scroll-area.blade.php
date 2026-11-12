@props([
    /** The tallest it grows before it scrolls: a number of pixels, or a CSS length ("20rem", "50dvh"). */
    'maxHeight' => null,
    /** A fixed height instead, the same units. */
    'height' => null,
    /** vertical | horizontal | both: which way the content may scroll. */
    'orientation' => 'vertical',
    /** A shadow at an edge while there is more content beyond it. */
    'shadows' => true,
    /**
     * The region's accessible name. Required when the content holds nothing
     * focusable, because then the region itself is the Tab stop a keyboard
     * scrolls it from, and a stop needs a name.
     */
    'label' => null,
    /** auto | thin | hidden-until-hover: the native scrollbar, quieter or not. */
    'scrollbar' => 'thin',
])

@php
    $orientation = in_array($orientation, ['vertical', 'horizontal', 'both'], true) ? $orientation : 'vertical';
    $scrollbar = in_array($scrollbar, ['auto', 'thin', 'hidden-until-hover'], true) ? $scrollbar : 'thin';

    /*
     * A length as a style value. Numbers are pixels; anything else must look
     * like one CSS length, so a stray value cannot write more of the style
     * attribute than the one declaration.
     */
    $length = function ($given, string $prop) {
        if ($given === null || $given === '') {
            return null;
        }
        if (is_numeric($given)) {
            return ((float) $given).'px';
        }
        $given = trim((string) $given);
        if (! preg_match('/^(\d+(\.\d+)?(px|rem|em|vh|dvh|svh|lvh|%)|calc\([\w\s.%+*\/()-]+\))$/', $given)) {
            throw new \InvalidArgumentException("ds::scroll-area's `{$prop}` must be a number of pixels or a CSS length, like 20rem; got [{$given}].");
        }

        return $given;
    };

    $style = collect([
        'max-height' => $length($maxHeight, 'maxHeight'),
        'height' => $length($height, 'height'),
    ])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode('; ');

    /*
     * The region is a Tab stop only when nothing inside it is one. With a
     * link or a button inside, Tab already scrolls the box to it, and a
     * second stop in front of them would be one press more for nothing.
     * Read from the rendered slot, so the stop is right before any script.
     */
    $html = (string) $slot;
    $interactive = (bool) preg_match(
        '/<(a\s[^>]*\bhref\b|button\b|select\b|textarea\b|summary\b|iframe\b|input\b(?![^>]*\btype\s*=\s*["\']?hidden)|[a-z][\w-]*\s[^>]*\b(tabindex\s*=\s*["\']?(?!-)\d|contenteditable\b))/i',
        $html,
    );
    $focusable = ! $interactive;

    if ($focusable && blank($label)) {
        throw new \InvalidArgumentException(
            'ds::scroll-area needs a `label` when its content holds nothing focusable: the region itself becomes the Tab stop a keyboard scrolls it from, and a stop with no name is read as "region".'
        );
    }

    $overflow = match ($orientation) {
        'horizontal' => 'overflow-x-auto overflow-y-hidden',
        'both' => 'overflow-auto',
        default => 'overflow-y-auto overflow-x-hidden',
    };

    $viewport = implode(' ', [
        // The area's own rounding, if the host gave it one, so the ring and
        // the content are clipped to it.
        'relative min-h-0 rounded-[inherit]', $overflow,
        // Room kept for the bar whether it shows or not, so hiding it until
        // hover never moves the content.
        $orientation !== 'horizontal' ? '[scrollbar-gutter:stable]' : '',
        'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-focus-ring',
    ]);

    $edges = match ($orientation) {
        'horizontal' => ['start', 'end'],
        'both' => ['top', 'bottom', 'start', 'end'],
        default => ['top', 'bottom'],
    };

    $edgeClass = [
        'top' => 'inset-x-0 top-0 h-3',
        'bottom' => 'inset-x-0 bottom-0 h-3',
        'start' => 'inset-y-0 start-0 w-3',
        'end' => 'inset-y-0 end-0 w-3',
    ];
@endphp

<div
    data-ds-scroll-area="{{ $orientation }}"
    data-ds-scrollbar="{{ $scrollbar }}"
    @if ($shadows) x-data x-ds-scroll-shadows @endif
    {{ $attributes->merge(['class' => 'relative flex min-h-0 min-w-0 flex-col overflow-hidden']) }}
>
    <div
        class="{{ $viewport }}"
        data-ds-scroll-viewport
        @if ($style !== '') style="{{ $style }}" @endif
        @if ($focusable) tabindex="0" @endif
        @if ($label) role="region" aria-label="{{ $label }}" @endif
    >{{ $slot }}</div>

    @if ($shadows)
        {{-- Each fades in while there is more content past its edge: from
             CSS where the browser has scroll-driven animations, from
             x-ds-scroll-shadows where it does not, and not at all with
             neither, which leaves the content scrolling as it would. --}}
        @foreach ($edges as $edge)
            <span class="pointer-events-none absolute {{ $edgeClass[$edge] }}" data-ds-scroll-edge="{{ $edge }}" aria-hidden="true"></span>
        @endforeach
    @endif
</div>
