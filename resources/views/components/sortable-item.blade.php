@aware([
    /*
     * The list's field name, disabled flag and words, from the
     * ds::sortable-list class: a key no other component has, for the reason
     * given in that class.
     */
    'dsSortable' => null,
])

@props([
    /** What the row posts, in `name[]`, and what the `reorder` event lists. */
    'value',
    /**
     * What the row is called when it is spoken of: the handle is "Reorder"
     * and this, the announcements and the buttons use it. Left out, the
     * row's text.
     */
    'label' => null,
    /** Stays where it is: no handle, no buttons. Other rows can still pass it. */
    'disabled' => false,
])

@php
    $list = $dsSortable ?? ['name' => null, 'disabled' => false, 'handle' => true, 'id' => 'ds-sortable', 'grab' => 'Reorder :item', 'up' => 'Move :item up', 'down' => 'Move :item down'];
    $name = $label ?? trim(preg_replace('/\s+/', ' ', strip_tags((string) $slot)));
    $fixed = $disabled || $list['disabled'];
    $words = fn (string $template) => str_replace(':item', $name, $template);

    // A row is a card the dragged one lifts out of. Its slide and its
    // settle are CSS transitions on the transform the script sets, so with
    // reduced motion the rows simply jump; data-instant is the script
    // turning them off for one frame while it measures.
    $row = implode(' ', [
        'group/sortable relative flex items-center gap-2 rounded-control border border-border bg-surface py-2 ps-1.5 pe-2 text-body text-fg-body shadow-raised',
        'motion-safe:transition-transform motion-safe:duration-popover-enter motion-safe:ease-enter',
        'data-dragging:z-10 data-dragging:shadow-float data-dragging:transition-none data-instant:transition-none',
        'data-lifted:z-10 data-lifted:shadow-float data-lifted:outline-2 data-lifted:outline-offset-2 data-lifted:outline-focus-ring',
    ]);

    $handle = implode(' ', [
        'flex size-8 shrink-0 touch-none items-center justify-center rounded-control text-fg-muted',
        'hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring',
        'motion-safe:transition-colors motion-safe:duration-popover-enter motion-safe:ease-enter',
        $fixed ? 'cursor-not-allowed opacity-40' : 'cursor-grab',
    ]);

    // Shown on a row's hover or focus, and always on a touch screen, which
    // has no hover: the route for a reader who cannot drag (WCAG 2.5.7).
    $move = 'flex size-8 items-center justify-center rounded-control text-fg-muted hover:bg-surface-subtle hover:text-fg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus-ring aria-disabled:pointer-events-none aria-disabled:opacity-40';
@endphp

<li
    data-ds-sortable-item
    data-value="{{ $value }}"
    data-ds-label="{{ $name }}"
    @if ($fixed) data-ds-sortable-fixed @endif
    {{ $attributes->merge(['class' => $row]) }}
>
    {{-- Hidden until the script is running: without it a handle would be a
         button that does nothing. Bound, so a morph keeps it shown. --}}
    <button
        type="button"
        hidden
        x-bind:hidden="! $data.dsReady"
        data-ds-sortable-handle
        aria-label="{{ $words($list['grab']) }}"
        aria-describedby="{{ $list['id'] }}-help"
        aria-pressed="false"
        @disabled($fixed)
        class="{{ $handle }}"
    >
        <svg class="size-4" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <circle cx="5.5" cy="3.5" r="1.25" /><circle cx="10.5" cy="3.5" r="1.25" />
            <circle cx="5.5" cy="8" r="1.25" /><circle cx="10.5" cy="8" r="1.25" />
            <circle cx="5.5" cy="12.5" r="1.25" /><circle cx="10.5" cy="12.5" r="1.25" />
        </svg>
    </button>

    <div class="min-w-0 flex-1">{{ $slot }}</div>

    @unless ($fixed)
        <span hidden x-bind:hidden="! $data.dsReady" class="flex shrink-0 opacity-0 motion-safe:transition-opacity motion-safe:duration-popover-enter motion-safe:ease-enter group-hover/sortable:opacity-100 group-focus-within/sortable:opacity-100 pointer-coarse:opacity-100">
            <button type="button" data-ds-sortable-up aria-label="{{ $words($list['up']) }}" class="{{ $move }}">
                <x-ds::icon name="chevron-up" variant="mini" size="4" />
            </button>
            <button type="button" data-ds-sortable-down aria-label="{{ $words($list['down']) }}" class="{{ $move }}">
                <x-ds::icon name="chevron-down" variant="mini" size="4" />
            </button>
        </span>
    @endunless

    @if ($list['name'])
        <input type="hidden" name="{{ $list['name'] }}[]" value="{{ $value }}">
    @endif
</li>
