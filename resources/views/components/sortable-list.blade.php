{{--
    ds::sortable-list. Its props are the constructor of
    Shipbytes\BladeUi\View\Components\SortableList, which also hands the
    items the field name, the disabled flag and their buttons' words: see
    that class for why this component is not anonymous.

    The rows are a plain list PHP renders in the server's order, each with
    its hidden input, so a form posts that order with no script at all.
    dsSortable adds the handles, the buttons and everything that moves.
--}}
@php
    $state = \Shipbytes\BladeUi\Support\Behaviour::data('dsSortable', [
        'handle' => $handle,
        'disabled' => $disabled,
        'words' => [
            'picked' => $pickedText,
            'moved' => $movedText,
            'dropped' => $droppedText,
            'cancelled' => $cancelledText,
        ],
    ]);
@endphp

{{-- relative: the placeholder is placed against this box while a row is
     dragged. The cursor and no text selection are the page's only while a
     row is being dragged. --}}
<div
    x-data="{{ $state }}"
    data-ds-sortable
    {{ $attributes->merge(['class' => 'relative data-dragging:cursor-grabbing data-dragging:select-none']) }}
>
    <ul
        role="list"
        @if ($label) aria-label="{{ $label }}" @endif
        data-ds-sortable-list
        class="flex flex-col gap-2"
    >{{ $slot }}</ul>

    {{-- Where the dragged row will land: drawn over the gap the other rows
         open for it. --}}
    <div data-ds-sortable-placeholder hidden aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 rounded-control border-2 border-dashed border-border-strong bg-surface-subtle"></div>

    <p id="{{ $id }}-help" hidden>{{ $instructions }}</p>
    <div role="status" data-ds-sortable-status class="sr-only"></div>
</div>
