@props([
    /** top-right | top-center | bottom-right | bottom-center */
    'position' => 'bottom-right',
    /** The region's name in a landmark list. */
    'label' => 'Notifications',
])

@php
    $positions = [
        'top-right' => 'top-0 right-0 items-end',
        'top-center' => 'top-0 left-1/2 -translate-x-1/2 items-center',
        'bottom-right' => 'bottom-0 right-0 items-end',
        'bottom-center' => 'bottom-0 left-1/2 -translate-x-1/2 items-center',
    ];

    $place = $positions[$position] ?? $positions['bottom-right'];
@endphp

{{--
    The region is rendered ALWAYS and empty most of the time.

    A live region only announces content that arrives after it is already in the
    document. Render the region at the same moment as the first toast and the
    first toast is silent — which is the one a reader most needs to hear.

    `polite`, not `assertive`: a toast reports something that already happened.
    Anything that must interrupt is not a toast, it is an alert or a modal.

    pointer-events-none on the region and auto on the toasts, so the empty
    container never swallows clicks on the page underneath it.
--}}
<div
    {{ $attributes->merge([
        'class' => "pointer-events-none fixed z-50 flex max-h-screen w-full max-w-sm flex-col gap-2 p-4 {$place}",
    ]) }}
    role="region"
    aria-label="{{ $label }}"
    aria-live="polite"
    aria-relevant="additions"
>
    {{ $slot }}
</div>
